<?php

namespace App\Services\Siprov;

use App\Enums\QuestionRoleEnum;
use App\Models\Audit;
use App\Models\CentralPatientAnswer;
use App\Models\Question;
use App\Models\TelemedicinaTenant;
use App\Models\TenantPlano;
use App\Models\TenantPlanoBeneficiario;
use App\Models\TenantQuantidadeParceiro;
use App\Services\Tenant\PacientePlanoService;
use App\Services\Tenant\PacientesDoParceiroService;
use App\Services\Tenant\TenantPlanoCotaService;
use App\Support\Planos;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Leva o plano dos associados da SIPROV para os parceiros (Página do Parceiro),
 * casando pelo CPF: completa o vínculo gravado sem plano e cria o vínculo do
 * paciente do parceiro que ficou sem nenhum (ex.: cadastro pelo formulário dinâmico).
 * Cada vínculo consome a vaga do plano (saldo -1, com o valor do plano) e entra no
 * histórico de registros; vínculos que já tinham plano mas não descontaram a vaga
 * (ex.: corrigidos antes do desconto existir) também são descontados.
 * Só desconta de quem é paciente do parceiro (tela Beneficiários); vaga descontada
 * por esta sincronização para quem não é paciente é devolvida. Paciente que já ocupa
 * a vaga sem registro no histórico de Planos (ex.: vínculo pelo modal) ganha o registro.
 */
class SiprovPlanoVinculoService
{
    public const ACAO_PLANO_PREENCHIDO = 'Plano preenchido';

    public const ACAO_VINCULO_CRIADO = 'Vínculo criado';

    public const ACAO_VAGA_DESCONTADA = 'Vaga descontada';

    public const ACAO_VAGA_DEVOLVIDA = 'Vaga devolvida';

    public const ACAO_HISTORICO_REGISTRADO = 'Histórico registrado';

    public const ACAO_HISTORICO_ATUALIZADO = 'Histórico atualizado';

    /**
     * @var array<int, array{plano: string, quantidade: int, saldo: int}>
     */
    private array $saldosIniciais = [];

    public const ORIGEM = 'sincronizacao_siprov';

    public function __construct(
        private readonly TenantPlanoCotaService $planoCotaService,
        private readonly PacientePlanoService $pacientePlanoService,
        private readonly PacientesDoParceiroService $pacientesDoParceiro,
    ) {}

    /**
     * @param  array<int, array>  $associados  itens da busca de associados da SIPROV
     * @param  bool  $simular  só lista o que seria alterado, sem gravar
     * @return array<int, array{acao: string, tenant_id: string, nome: string, cpf: string, plano: string, vaga: string}>
     */
    public function sincronizar(array $associados, bool $simular = false): array
    {
        $porCpf = $this->associadosComPlanoPorCpf($associados);
        $devolvidas = $this->devolverSemPaciente($simular);

        $linhas = $porCpf
            ? [...$this->completarPlanos($porCpf, $simular), ...$this->criarVinculosFaltantes($porCpf, $simular)]
            : [];

        $linhas = [
            ...$devolvidas,
            ...$linhas,
            ...$this->descontarPendentes(array_filter(array_column($linhas, 'vinculo_id')), $simular),
        ];

        // Na simulação os descontos acima não gravaram registro: não contam como faltantes.
        return [...$linhas, ...$this->registrarHistoricoFaltante($simular ? array_filter(array_column($linhas, 'vinculo_id')) : [], $simular)];
    }

    /**
     * Vínculos do parceiro gravados sem plano recebem o plano da SIPROV.
     *
     * @param  array<string, array>  $porCpf
     */
    private function completarPlanos(array $porCpf, bool $simular): array
    {
        $alterados = [];

        TelemedicinaTenant::whereNotNull('data->siprov_id')
            ->orderBy('id')
            ->get()
            ->each(function (TelemedicinaTenant $vinculo) use ($porCpf, $simular, &$alterados) {
                $data = $vinculo->data ?? [];
                $cpf = preg_replace('/\D/', '', (string) ($data['cpf_cnpj'] ?? ''));
                $item = $porCpf[$cpf] ?? null;

                if (! $item || TenantPlanoCotaService::codigosDoVinculo($data)) {
                    return;
                }

                $vinculo->data = [
                    ...$data,
                    ...$this->dadosDoPlano($item),
                    'codBeneficio' => ($data['codBeneficio'] ?? null) ?: ($item['codBeneficio'] ?? null),
                ];

                if (! $simular) {
                    $vinculo->save();
                }

                $alterados[] = $this->linha(self::ACAO_PLANO_PREENCHIDO, $vinculo, $simular);
            });

        return $alterados;
    }

    /**
     * Paciente do parceiro (mesmo CPF) sem nenhum vínculo de plano: cria o vínculo
     * com o plano da SIPROV, como faz o vínculo pela Página do Parceiro.
     *
     * @param  array<string, array>  $porCpf
     */
    private function criarVinculosFaltantes(array $porCpf, bool $simular): array
    {
        $cpfQuestion = Question::where('role', QuestionRoleEnum::Cpf)->first();

        if (! $cpfQuestion) {
            return [];
        }

        $pacientes = CentralPatientAnswer::where('question_id', $cpfQuestion->id)
            ->whereIn('answer', array_keys($porCpf))
            ->with('patient:id,tenant_id')
            ->get()
            ->filter(fn (CentralPatientAnswer $answer) => $answer->patient?->tenant_id)
            ->map(fn (CentralPatientAnswer $answer) => ['tenant_id' => (string) $answer->patient->tenant_id, 'cpf' => (string) $answer->answer])
            ->unique(fn (array $paciente) => $paciente['tenant_id'].'|'.$paciente['cpf']);

        if ($pacientes->isEmpty()) {
            return [];
        }

        $vinculados = $this->vinculadosPorTenantECpf($pacientes->pluck('tenant_id')->unique()->all());
        $criados = [];

        foreach ($pacientes as $paciente) {
            // O espelho do central pode ter paciente já excluído do parceiro.
            if (isset($vinculados[$paciente['tenant_id'].'|'.$paciente['cpf']])
                || ! $this->pacientesDoParceiro->ehPaciente($paciente['tenant_id'], $paciente['cpf'])) {
                continue;
            }

            $item = $porCpf[$paciente['cpf']];

            $vinculo = new TelemedicinaTenant([
                'tenant_id' => $paciente['tenant_id'],
                'data' => [
                    'siprov_id' => $item['codPessoa'] ?? null,
                    'title' => $item['nomePessoa'] ?? '',
                    'cpf_cnpj' => $paciente['cpf'],
                    ...$this->dadosDoPlano($item),
                    'codigo_integracao' => $item['codPessoa'] ?? null,
                    'codBeneficio' => $item['codBeneficio'] ?? null,
                    'origem' => self::ORIGEM,
                ],
            ]);

            if (! $simular) {
                $vinculo->save();
            }

            $criados[] = $this->linha(self::ACAO_VINCULO_CRIADO, $vinculo, $simular);
        }

        return $criados;
    }

    /**
     * Vínculos com plano que não consumiram a vaga. Só os gravados depois de o plano
     * ser habilitado no parceiro (e da última zeragem): os anteriores já entraram no
     * saldo inicial do plano.
     *
     * @param  array<int, int>  $ignorar  vínculos já tratados nesta execução
     */
    private function descontarPendentes(array $ignorar, bool $simular): array
    {
        $consumidos = TenantQuantidadeParceiro::where('tipo', TenantQuantidadeParceiro::TIPO_CONSUMO)
            ->whereNotNull('telemedicina_tenant_id')
            ->distinct()
            ->pluck('telemedicina_tenant_id')
            ->all();

        $vinculos = TelemedicinaTenant::whereNotNull('data->siprov_id')
            ->whereNotIn('id', [...$consumidos, ...$ignorar])
            ->orderBy('id')
            ->get();

        if ($vinculos->isEmpty()) {
            return [];
        }

        $tenantIds = $vinculos->pluck('tenant_id')->unique()->all();

        $habilitadoEm = TenantPlano::whereIn('tenant_id', $tenantIds)
            ->get(['tenant_id', 'cod_plano', 'created_at'])
            ->mapWithKeys(fn (TenantPlano $plano) => [$plano->tenant_id.'|'.$plano->cod_plano => $plano->created_at]);

        $zeradoEm = TenantQuantidadeParceiro::where('tipo', TenantQuantidadeParceiro::TIPO_ZERAGEM)
            ->whereIn('tenant_id', $tenantIds)
            ->selectRaw('tenant_id, cod_plano, max(created_at) as ultima')
            ->groupBy('tenant_id', 'cod_plano')
            ->get()
            ->mapWithKeys(fn ($zeragem) => [$zeragem->tenant_id.'|'.$zeragem->cod_plano => Carbon::parse($zeragem->ultima)]);

        return $vinculos
            ->filter(function (TelemedicinaTenant $vinculo) use ($habilitadoEm, $zeradoEm) {
                $codigos = TenantPlanoCotaService::codigosDoVinculo($vinculo->data ?? []);

                if (! $codigos || ! $this->pacientesDoParceiro->ehPaciente($vinculo->tenant_id, $vinculo->data['cpf_cnpj'] ?? null)) {
                    return false;
                }

                return collect($codigos)->every(function (string $codigo) use ($vinculo, $habilitadoEm, $zeradoEm) {
                    $chave = $vinculo->tenant_id.'|'.$codigo;

                    return isset($habilitadoEm[$chave])
                        && $vinculo->updated_at > $habilitadoEm[$chave]
                        && (! isset($zeradoEm[$chave]) || $vinculo->updated_at > $zeradoEm[$chave]);
                });
            })
            ->map(fn (TelemedicinaTenant $vinculo) => $this->linha(self::ACAO_VAGA_DESCONTADA, $vinculo, $simular))
            ->values()
            ->all();
    }

    /**
     * Vínculos que esta sincronização descontou, mas cujo CPF não é paciente do
     * parceiro: devolve a vaga (movimento de devolução, com valor) e remove o
     * registro feito por engano do histórico de Planos.
     */
    private function devolverSemPaciente(bool $simular): array
    {
        $registros = Audit::where('event', 'registro_plano')
            ->where('auditable_type', TelemedicinaTenant::class)
            ->where('new_values', 'like', '%'.self::ORIGEM.'%')
            ->get()
            ->filter(fn (Audit $audit) => ($audit->new_values['origem'] ?? null) === self::ORIGEM)
            ->groupBy('auditable_id');

        if ($registros->isEmpty()) {
            return [];
        }

        // Vínculos com vaga ainda descontada (consumo sem a devolução correspondente).
        $descontados = TenantQuantidadeParceiro::whereIn('telemedicina_tenant_id', $registros->keys())
            ->groupBy('telemedicina_tenant_id')
            ->havingRaw('sum(variacao) < 0')
            ->pluck('telemedicina_tenant_id')
            ->all();

        return TelemedicinaTenant::whereIn('id', $descontados)
            ->orderBy('id')
            ->get()
            ->reject(fn (TelemedicinaTenant $vinculo) => $this->pacientesDoParceiro->ehPaciente($vinculo->tenant_id, $vinculo->data['cpf_cnpj'] ?? null))
            ->map(function (TelemedicinaTenant $vinculo) use ($registros, $simular) {
                if (! $simular) {
                    $this->planoCotaService->devolver($vinculo);
                    Audit::whereIn('id', $registros[$vinculo->id]->pluck('id'))->delete();
                }

                $linha = $this->linhaSemDesconto(self::ACAO_VAGA_DEVOLVIDA, $vinculo);
                $linha['vaga'] = $simular ? 'Seria devolvida: não é paciente do parceiro' : 'Devolvida: não é paciente do parceiro';

                return $linha;
            })
            ->values()
            ->all();
    }

    /**
     * Pacientes do parceiro que ocupam vaga do plano mas não têm registro no histórico
     * de Planos (vínculo pelo modal, ou já contados quando o plano foi habilitado):
     * cria o registro com a data, o saldo e o valor do desconto, sem descontar de novo.
     *
     * @param  array<int, int>  $ignorar  vínculos já tratados nesta execução
     */
    private function registrarHistoricoFaltante(array $ignorar, bool $simular): array
    {
        $vinculos = TelemedicinaTenant::whereNotNull('data->siprov_id')
            ->whereNotIn('id', $ignorar)
            ->orderBy('id')
            ->get();

        if ($vinculos->isEmpty()) {
            return [];
        }

        $registrados = Audit::where('event', 'registro_plano')
            ->where('auditable_type', TelemedicinaTenant::class)
            ->whereIn('auditable_id', $vinculos->pluck('id'))
            ->get()
            ->groupBy('auditable_id');

        $movimentos = TenantQuantidadeParceiro::whereIn('telemedicina_tenant_id', $vinculos->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('telemedicina_tenant_id');

        $planos = TenantPlano::whereIn('tenant_id', $vinculos->pluck('tenant_id')->unique())
            ->get()
            ->keyBy(fn (TenantPlano $plano) => $plano->tenant_id.'|'.$plano->cod_plano);

        $linhas = [];

        foreach ($vinculos as $vinculo) {
            $codigos = TenantPlanoCotaService::codigosDoVinculo($vinculo->data ?? []);
            $movimentosDoVinculo = $movimentos[$vinculo->id] ?? collect();

            if (! $codigos || ! $this->pacientesDoParceiro->ehPaciente($vinculo->tenant_id, $vinculo->data['cpf_cnpj'] ?? null)) {
                continue;
            }

            if (isset($registrados[$vinculo->id])) {
                if ($linha = $this->completarSaldoDoRegistro($vinculo, $registrados[$vinculo->id], $movimentosDoVinculo->isEmpty(), $planos, $simular)) {
                    $linhas[] = $linha;
                }

                continue;
            }

            // Ocupa a vaga: consumo ainda não devolvido, ou (sem movimento) já existia
            // quando o plano foi habilitado e entrou no saldo inicial.
            $ocupando = collect($codigos)
                ->map(fn (string $codigo) => $planos[$vinculo->tenant_id.'|'.$codigo] ?? null)
                ->filter(fn (?TenantPlano $plano) => $plano && ($movimentosDoVinculo->isNotEmpty()
                    ? $movimentosDoVinculo->where('cod_plano', $plano->cod_plano)->sum('variacao') < 0
                    : $vinculo->updated_at <= $plano->created_at));

            if ($ocupando->isEmpty()) {
                continue;
            }

            if (! $simular) {
                $ocupando->each(fn (TenantPlano $plano) => $this->criarRegistroHistorico(
                    $vinculo,
                    $plano,
                    $movimentosDoVinculo->where('cod_plano', $plano->cod_plano)->firstWhere('tipo', TenantQuantidadeParceiro::TIPO_CONSUMO),
                ));
            }

            $linha = $this->linhaSemDesconto(self::ACAO_HISTORICO_REGISTRADO, $vinculo);
            $linha['vaga'] = $simular ? 'Seria registrado (vaga já ocupada)' : 'Registrado (vaga já ocupada)';
            $linhas[] = $linha;
        }

        return $linhas;
    }

    /**
     * Registro no histórico de Planos com os dados de quando a vaga foi ocupada.
     */
    private function criarRegistroHistorico(TelemedicinaTenant $vinculo, TenantPlano $plano, ?TenantQuantidadeParceiro $consumo): void
    {
        $data = $vinculo->data ?? [];

        $audit = Audit::create([
            'event' => 'registro_plano',
            'auditable_type' => TelemedicinaTenant::class,
            'auditable_id' => $vinculo->id,
            'old_values' => [],
            'new_values' => [
                'tenant_id' => $vinculo->tenant_id,
                'paciente_id' => $data['patient_id'] ?? null,
                'paciente' => $data['title'] ?? '',
                'usuario' => null,
                'origem' => $data['origem'] ?? 'vinculo_siprov',
                'cod_plano' => $plano->cod_plano,
                'plano' => $this->label($plano->cod_plano),
                'valor' => $consumo?->valor ?? $plano->valor,
                // Saldo logo após a vaga ser ocupada; sem o movimento, o saldo inicial do plano.
                'planos' => [$plano->cod_plano => $consumo
                    ? ['plano' => $this->label($plano->cod_plano), 'quantidade' => $plano->quantidade, 'saldo' => $consumo->quantidade]
                    : $this->saldoInicial($plano)],
            ],
            'tags' => 'tenant:'.$vinculo->tenant_id,
        ]);

        $audit->forceFill(['created_at' => $consumo?->created_at ?? $vinculo->created_at])->save();
    }

    /**
     * Registro do histórico criado sem saldo (vaga contada no saldo inicial, antes de
     * o saldo inicial ser preenchido): completa com o saldo inicial do plano.
     *
     * @param  Collection<int, Audit>  $registros
     * @param  Collection<string, TenantPlano>  $planos
     */
    private function completarSaldoDoRegistro(TelemedicinaTenant $vinculo, $registros, bool $semMovimento, $planos, bool $simular): ?array
    {
        $semSaldo = $registros->filter(fn (Audit $audit) => $semMovimento
            && empty($audit->new_values['planos'])
            && isset($planos[$vinculo->tenant_id.'|'.($audit->new_values['cod_plano'] ?? '')]));

        if ($semSaldo->isEmpty()) {
            return null;
        }

        if (! $simular) {
            $semSaldo->each(function (Audit $audit) use ($vinculo, $planos) {
                $plano = $planos[$vinculo->tenant_id.'|'.$audit->new_values['cod_plano']];

                $audit->new_values = [...$audit->new_values, 'planos' => [$plano->cod_plano => $this->saldoInicial($plano)]];
                $audit->save();
            });
        }

        $linha = $this->linhaSemDesconto(self::ACAO_HISTORICO_ATUALIZADO, $vinculo);
        $linha['vaga'] = $simular ? 'Saldo inicial seria preenchido' : 'Saldo inicial preenchido';

        return $linha;
    }

    /**
     * Saldo e quantidade contratada logo que o plano foi habilitado, refeitos pelo
     * extrato: a criação do plano grava um ajuste com o saldo inicial; planos
     * anteriores ao extrato partem do saldo antes do primeiro movimento.
     *
     * @return array{plano: string, quantidade: int, saldo: int}
     */
    private function saldoInicial(TenantPlano $plano): array
    {
        if (isset($this->saldosIniciais[$plano->id])) {
            return $this->saldosIniciais[$plano->id];
        }

        $movimentos = TenantQuantidadeParceiro::where('tenant_id', $plano->tenant_id)
            ->where('cod_plano', $plano->cod_plano)
            ->orderBy('id')
            ->get();

        $primeiro = $movimentos->first();
        $criacao = $primeiro
            && $primeiro->tipo === TenantQuantidadeParceiro::TIPO_AJUSTE
            && $primeiro->created_at
            && $plano->created_at
            && abs($primeiro->created_at->diffInSeconds($plano->created_at)) <= 5;

        $saldo = match (true) {
            ! $primeiro => $plano->saldo,
            $criacao => $primeiro->quantidade,
            default => $primeiro->quantidade - $primeiro->variacao,
        };

        // Ajustes de quantidade feitos depois da criação mudaram o contratado.
        $ajustes = $movimentos->where('tipo', TenantQuantidadeParceiro::TIPO_AJUSTE)
            ->reject(fn (TenantQuantidadeParceiro $movimento) => $criacao && $movimento->is($primeiro))
            ->sum('variacao');

        return $this->saldosIniciais[$plano->id] = [
            'plano' => $this->label($plano->cod_plano),
            'quantidade' => $plano->quantidade - $ajustes,
            'saldo' => $saldo,
        ];
    }

    /**
     * Consome a vaga de cada plano do vínculo e registra no histórico de registros.
     * Sem vaga (ou plano não habilitado), o vínculo fica com o plano e nada é descontado.
     *
     * @return string resultado do desconto, para a saída do comando
     */
    private function descontarVaga(TelemedicinaTenant $vinculo, bool $simular): string
    {
        $codigos = TenantPlanoCotaService::codigosDoVinculo($vinculo->data ?? []);

        if (! $this->pacientesDoParceiro->ehPaciente($vinculo->tenant_id, $vinculo->data['cpf_cnpj'] ?? null)) {
            return 'Não descontada: não é paciente do parceiro';
        }

        if ($simular) {
            $planos = TenantPlano::where('tenant_id', $vinculo->tenant_id)->whereIn('cod_plano', $codigos)->get()->keyBy('cod_plano');

            foreach ($codigos as $codigo) {
                if (! isset($planos[$codigo])) {
                    return 'Não descontada: plano '.$this->label($codigo).' não habilitado no parceiro';
                }

                if ($planos[$codigo]->saldo < 1) {
                    return 'Não descontada: plano '.$this->label($codigo).' sem vaga';
                }
            }

            return 'Seria descontada';
        }

        try {
            $this->planoCotaService->consumir($vinculo->tenant_id, $codigos, null, $vinculo->id);
        } catch (ValidationException $e) {
            return 'Não descontada: '.collect($e->errors())->flatten()->implode(' ');
        }

        foreach ($codigos as $codigo) {
            $this->pacientePlanoService->auditarRegistro(
                $vinculo,
                $vinculo->tenant_id,
                $codigo,
                $this->label($codigo),
                (string) ($vinculo->data['title'] ?? ''),
                null,
                self::ORIGEM,
            );
        }

        return TenantPlano::where('tenant_id', $vinculo->tenant_id)
            ->whereIn('cod_plano', $codigos)
            ->get()
            ->map(fn (TenantPlano $plano) => "Descontada: saldo {$plano->saldo} de {$plano->quantidade}"
                .($plano->valor !== null ? ' · R$ '.number_format((float) $plano->valor, 2, ',', '.') : ''))
            ->implode('; ');
    }

    /**
     * Chaves "tenant|cpf" (CPF só dígitos) que já têm vínculo: telemedicina ou plano interno.
     *
     * @param  array<int, string>  $tenantIds
     * @return array<string, true>
     */
    private function vinculadosPorTenantECpf(array $tenantIds): array
    {
        $vinculados = [];

        TelemedicinaTenant::whereIn('tenant_id', $tenantIds)
            ->whereNotNull('data->siprov_id')
            ->get(['tenant_id', 'data'])
            ->each(function (TelemedicinaTenant $vinculo) use (&$vinculados) {
                $vinculados[$vinculo->tenant_id.'|'.preg_replace('/\D/', '', (string) ($vinculo->data['cpf_cnpj'] ?? ''))] = true;
            });

        TenantPlanoBeneficiario::whereIn('tenant_id', $tenantIds)
            ->whereNotNull('cpf')
            ->get(['tenant_id', 'cpf'])
            ->each(function (TenantPlanoBeneficiario $vinculo) use (&$vinculados) {
                $vinculados[$vinculo->tenant_id.'|'.$vinculo->cpf] = true;
            });

        return $vinculados;
    }

    /**
     * @return array{cod_plano: mixed, cod_planos: array<int, string>, plano_label: string}
     */
    private function dadosDoPlano(array $item): array
    {
        return [
            'cod_plano' => $item['planos'][0]['codPlano'],
            'cod_planos' => TenantPlanoCotaService::codigosDoItemSiprov($item),
            'plano_label' => $item['planos'][0]['nome'] ?? '',
        ];
    }

    /**
     * @return array{acao: string, tenant_id: string, nome: string, cpf: string, plano: string, vaga: string, vinculo_id: ?int}
     */
    private function linha(string $acao, TelemedicinaTenant $vinculo, bool $simular): array
    {
        return [...$this->linhaSemDesconto($acao, $vinculo), 'vaga' => $this->descontarVaga($vinculo, $simular)];
    }

    /**
     * @return array{acao: string, tenant_id: string, nome: string, cpf: string, plano: string, vaga: string, vinculo_id: ?int}
     */
    private function linhaSemDesconto(string $acao, TelemedicinaTenant $vinculo): array
    {
        $data = $vinculo->data ?? [];

        return [
            'acao' => $acao,
            'tenant_id' => (string) $vinculo->tenant_id,
            'nome' => (string) ($data['title'] ?? ''),
            'cpf' => preg_replace('/\D/', '', (string) ($data['cpf_cnpj'] ?? '')),
            'plano' => (string) ($data['plano_label'] ?? ''),
            'vaga' => '',
            'vinculo_id' => $vinculo->id,
        ];
    }

    private function label(string $codigo): string
    {
        return collect(Planos::options())->pluck('label', 'value')[$codigo] ?? "Plano {$codigo}";
    }

    /**
     * Associados da SIPROV que têm plano, por CPF (só dígitos); o primeiro com plano vence.
     *
     * @return array<string, array>
     */
    private function associadosComPlanoPorCpf(array $associados): array
    {
        $porCpf = [];

        foreach ($associados as $item) {
            $cpf = preg_replace('/\D/', '', (string) ($item['cpfCnpj'] ?? ''));

            if ($cpf === '' || isset($porCpf[$cpf]) || ! TenantPlanoCotaService::codigosDoItemSiprov($item)) {
                continue;
            }

            // Só os planos com código, para o primeiro ser sempre válido.
            $item['planos'] = array_values(array_filter($item['planos'], fn ($plano) => ($plano['codPlano'] ?? '') !== ''));
            $porCpf[$cpf] = $item;
        }

        return $porCpf;
    }
}
