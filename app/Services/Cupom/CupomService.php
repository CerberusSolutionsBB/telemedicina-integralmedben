<?php

namespace App\Services\Cupom;

use App\Models\Cupom;
use App\Models\CupomUso;
use App\Models\TelemedicinaTenant;
use App\Models\TenantPlano;
use App\Models\TenantPlanoBeneficiario;
use App\Models\TenantsDetail;
use App\Support\Formatar;
use App\Support\Planos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cupons de desconto na venda de planos: validação, aplicação (com lock, dentro
 * da transação do vínculo), estorno na devolução da vaga e teto do parceiro.
 */
class CupomService
{
    /** Chave do teto (% máximo de desconto) dos cupons do parceiro em tenants_details.configuracao. */
    public const CHAVE_TETO = 'cupom_desconto_maximo';

    /**
     * Teto de desconto (%) dos cupons criados pelo parceiro; null = o parceiro não pode criar cupons.
     */
    public function tetoDoTenant(string $tenantId): ?float
    {
        $teto = TenantsDetail::where('tenant_id', $tenantId)->first()?->configuracao[self::CHAVE_TETO] ?? null;

        return $teto === null ? null : (float) $teto;
    }

    public function definirTeto(string $tenantId, ?float $teto): void
    {
        $detail = TenantsDetail::firstOrCreate(['tenant_id' => $tenantId]);

        $configuracao = $detail->configuracao ?? [];
        $configuracao[self::CHAVE_TETO] = $teto;

        $detail->update(['configuracao' => $configuracao]);
    }

    /**
     * Cria ou atualiza o cupom. $tenantId fixo = cupom do parceiro (painel do tenant);
     * no painel central o tenant vem dos dados (null = global).
     *
     * @param  array<string, mixed>  $dados  validados por CupomRequest
     */
    public function salvar(array $dados, ?Cupom $cupom = null, ?string $tenantFixo = null): Cupom
    {
        return DB::connection('mysql')->transaction(function () use ($dados, $cupom, $tenantFixo) {
            $atributos = [
                'tipo' => $dados['tipo'],
                'valor' => $dados['valor'],
                'origem' => $dados['origem'],
                'inicio_em' => Formatar::localParaUtc($dados['inicio_em']),
                'expira_em' => Formatar::localParaUtc($dados['expira_em']),
                'limite_uso_total' => $dados['limite_uso_total'] ?? null,
                'limite_uso_por_cpf' => $dados['limite_uso_por_cpf'],
                'valor_minimo' => $dados['valor_minimo'] ?? 0,
                'aplicavel_a' => $dados['aplicavel_a'],
                'observacoes' => $dados['observacoes'] ?? null,
            ];

            if (! $cupom) {
                $cupom = Cupom::create([
                    ...$atributos,
                    'codigo' => filled($dados['codigo'] ?? null) ? $dados['codigo'] : $this->gerarCodigo(),
                    'tenant_id' => $tenantFixo ?? ($dados['tenant_id'] ?? null),
                    'criado_por_id' => auth()->id(),
                    'criado_por_nome' => auth()->user()?->name,
                ]);
            } else {
                $cupom->update([
                    ...$atributos,
                    'codigo' => filled($dados['codigo'] ?? null) ? $dados['codigo'] : $cupom->codigo,
                    // O parceiro nunca troca o dono do cupom; o admin pode.
                    'tenant_id' => $tenantFixo ?? ($dados['tenant_id'] ?? null),
                ]);
            }

            $cupom->planos()->delete();

            if ($cupom->aplicavel_a === Cupom::APLICAVEL_PLANOS) {
                $cupom->planos()->createMany(collect($dados['planos'] ?? [])->unique()->map(fn ($codigo) => ['cod_plano' => (string) $codigo])->all());
            }

            return $cupom;
        });
    }

    /**
     * Troca a situação (ativo, pausado, cancelado). Cancelado é final.
     *
     * @throws ValidationException
     */
    public function alterarStatus(Cupom $cupom, string $status): void
    {
        if ($cupom->status === Cupom::STATUS_CANCELADO && $status !== Cupom::STATUS_CANCELADO) {
            $this->falhar('Cupom cancelado não pode voltar a valer. Crie um novo cupom.');
        }

        $cupom->update(['status' => $status]);
    }

    /**
     * Exclui o cupom. Cupom já usado (mesmo estornado) não sai: os usos guardam o
     * snapshot das vendas; nesse caso o caminho é cancelar.
     *
     * @throws ValidationException
     */
    public function excluir(Cupom $cupom): void
    {
        if ($cupom->usosRegistrados()->exists()) {
            $this->falhar('Este cupom já foi usado e não pode ser excluído. Cancele-o para que deixe de valer.');
        }

        $cupom->delete();
    }

    /**
     * Código aleatório de 8 caracteres, sem os ambíguos (0/O, 1/I).
     */
    public function gerarCodigo(): string
    {
        do {
            $codigo = collect(range(1, 8))->map(fn () => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'[random_int(0, 31)])->implode('');
        } while (Cupom::where('codigo', $codigo)->exists());

        return $codigo;
    }

    /**
     * Cupom no formato das telas (lista e formulário de edição).
     *
     * @return array<string, mixed>
     */
    public function paraTela(Cupom $cupom): array
    {
        $situacao = $cupom->situacao();

        return [
            'id' => $cupom->id,
            'codigo' => $cupom->codigo,
            'tipo' => $cupom->tipo,
            'valor' => (float) $cupom->valor,
            'tenant_id' => $cupom->tenant_id,
            'parceiro' => $cupom->tenant_id ? ($cupom->tenant?->details?->first()?->descricao ?? $cupom->tenant_id) : null,
            'origem' => $cupom->origem,
            'inicio_em' => $cupom->inicio_em?->setTimezone(Formatar::FUSO)->format('Y-m-d\TH:i'),
            'expira_em' => $cupom->expira_em?->setTimezone(Formatar::FUSO)->format('Y-m-d\TH:i'),
            'periodo' => Formatar::dataHora($cupom->inicio_em).' a '.Formatar::dataHora($cupom->expira_em),
            'limite_uso_total' => $cupom->limite_uso_total,
            'limite_uso_por_cpf' => $cupom->limite_uso_por_cpf,
            'usos' => $cupom->usos,
            // withExists('usosRegistrados') na listagem evita uma consulta por cupom.
            'pode_excluir' => ! ($cupom->usos_registrados_exists ?? $cupom->usosRegistrados()->exists()),
            'status' => $cupom->status,
            'situacao' => $situacao,
            'valor_minimo' => (float) $cupom->valor_minimo,
            'aplicavel_a' => $cupom->aplicavel_a,
            'planos' => $cupom->planos->pluck('cod_plano')->map(fn ($c) => (string) $c)->values()->all(),
            'planos_nomes' => $cupom->planos->map(fn ($p) => collect(Planos::options())->pluck('label', 'value')[(string) $p->cod_plano] ?? $p->cod_plano)->values()->all(),
            'tipo_label' => Cupom::TIPOS[$cupom->tipo] ?? $cupom->tipo,
            'origem_label' => Cupom::ORIGENS[$cupom->origem] ?? $cupom->origem,
            'aplicavel_label' => Cupom::APLICAVEIS[$cupom->aplicavel_a] ?? $cupom->aplicavel_a,
            'inicio_formatado' => Formatar::dataHora($cupom->inicio_em),
            'expira_formatado' => Formatar::dataHora($cupom->expira_em),
            'atualizado_em' => Formatar::dataHora($cupom->updated_at),
            'observacoes' => $cupom->observacoes,
            'criado_por' => $cupom->criado_por_nome,
            'criado_em' => Formatar::dataHora($cupom->created_at),
        ];
    }

    /**
     * Histórico de usos do cupom (mais recentes primeiro).
     *
     * @return array<int, array<string, mixed>>
     */
    public function usosParaTela(Cupom $cupom): array
    {
        return $cupom->usosRegistrados()->latest()->limit(200)->get()
            ->map(fn (CupomUso $uso) => [
                'id' => $uso->id,
                'paciente' => $uso->paciente_nome,
                'cpf' => Formatar::cpf($uso->cpf),
                'plano' => collect(Planos::options())->pluck('label', 'value')[$uso->cod_plano] ?? $uso->cod_plano,
                'valor_original' => (float) $uso->valor_original,
                'desconto' => (float) $uso->desconto,
                'valor_final' => (float) $uso->valor_final,
                'usuario' => $uso->usuario_nome,
                'data' => Formatar::dataHora($uso->created_at),
                'estornado_em' => Formatar::dataHora($uso->estornado_em),
            ])
            ->all();
    }

    /**
     * Prévia do desconto (sem lock), para validar antes de salvar e mostrar ao usuário.
     *
     * @return array{cupom: Cupom, valor_original: float, desconto: float, valor_final: float}
     *
     * @throws ValidationException
     */
    public function validar(string $codigo, string $tenantId, string $codPlano, ?string $cpf): array
    {
        $cupom = Cupom::with('planos')->where('codigo', Cupom::normalizarCodigo($codigo))->first();

        return $this->calcular($cupom, $tenantId, $codPlano, $cpf);
    }

    /**
     * Aplica o cupom na venda. Deve rodar dentro da transação (conexão mysql) que
     * cria o vínculo e consome a vaga: se o cupom não valer mais, nada é gravado.
     *
     * @throws ValidationException
     */
    public function aplicar(string $codigo, string $tenantId, string $codPlano, string $cpf, string $nome, Model $vinculo): CupomUso
    {
        $cupom = Cupom::with('planos')->where('codigo', Cupom::normalizarCodigo($codigo))->lockForUpdate()->first();

        $calculo = $this->calcular($cupom, $tenantId, $codPlano, $cpf, $vinculo);

        $cupom->increment('usos');

        return CupomUso::create([
            'cupom_id' => $cupom->id,
            'tenant_id' => $tenantId,
            'cpf' => $this->digitos($cpf),
            'paciente_nome' => $nome,
            'cod_plano' => $codPlano,
            'origem_type' => $vinculo::class,
            'origem_id' => $vinculo->getKey(),
            'valor_original' => $calculo['valor_original'],
            'desconto' => $calculo['desconto'],
            'valor_final' => $calculo['valor_final'],
            'user_id' => auth()->id(),
            'usuario_nome' => auth()->user()?->name,
            'ip' => request()?->ip(),
        ]);
    }

    /**
     * Vaga devolvida: o uso do cupom é estornado e volta a contar como disponível.
     */
    public function estornar(Model $vinculo): void
    {
        DB::connection('mysql')->transaction(function () use ($vinculo) {
            CupomUso::validos()
                ->where('origem_type', $vinculo::class)
                ->where('origem_id', $vinculo->getKey())
                ->lockForUpdate()
                ->get()
                ->each(function (CupomUso $uso) {
                    $uso->update(['estornado_em' => now()]);

                    Cupom::whereKey($uso->cupom_id)->where('usos', '>', 0)->decrement('usos');
                });
        });
    }

    /**
     * @return array{cupom: Cupom, valor_original: float, desconto: float, valor_final: float}
     *
     * @throws ValidationException
     */
    private function calcular(?Cupom $cupom, string $tenantId, string $codPlano, ?string $cpf, ?Model $vinculoAtual = null): array
    {
        if (! $cupom || $cupom->alvo !== 'venda' || ($cupom->tenant_id !== null && $cupom->tenant_id !== $tenantId)) {
            $this->falhar('Cupom inválido.');
        }

        $mensagens = [
            Cupom::STATUS_PAUSADO => 'Este cupom está pausado.',
            Cupom::STATUS_CANCELADO => 'Este cupom foi cancelado.',
            'expirado' => 'Este cupom expirou.',
            'agendado' => 'Este cupom ainda não está válido.',
            'esgotado' => 'Este cupom atingiu o limite de usos.',
        ];

        if ($situacao = $mensagens[$cupom->situacao()] ?? null) {
            $this->falhar($situacao);
        }

        $cpf = $this->digitos($cpf);

        if ($cpf === '') {
            $this->falhar('Informe o CPF para usar o cupom.');
        }

        $usosDoCpf = CupomUso::validos()->where('cupom_id', $cupom->id)->where('cpf', $cpf)->count();

        if ($usosDoCpf >= $cupom->limite_uso_por_cpf) {
            $this->falhar('Este CPF já usou este cupom.');
        }

        if ($cupom->aplicavel_a === Cupom::APLICAVEL_PLANOS && ! $cupom->planos->pluck('cod_plano')->map(fn ($c) => (string) $c)->contains($codPlano)) {
            $this->falhar('Este cupom não vale para o plano escolhido.');
        }

        if ($cupom->aplicavel_a === Cupom::APLICAVEL_PRIMEIRO_CONTRATO && $this->jaContratou($tenantId, $cpf, $vinculoAtual)) {
            $this->falhar('Este cupom vale só para o primeiro contrato do CPF.');
        }

        $valor = TenantPlano::where('tenant_id', $tenantId)->where('cod_plano', $codPlano)->value('valor');

        if ($valor === null || (float) $valor <= 0) {
            $this->falhar('O plano escolhido não tem valor definido; o cupom não pode ser aplicado.');
        }

        $valor = (float) $valor;

        if ($valor < (float) $cupom->valor_minimo) {
            $this->falhar('O valor do plano não atinge o mínimo deste cupom.');
        }

        $desconto = $cupom->descontoSobre($valor);

        // Cupom do parceiro: o desconto respeita o teto atual definido pelo admin.
        if ($cupom->tenant_id !== null) {
            $teto = $this->tetoDoTenant($tenantId);

            if ($teto === null) {
                $this->falhar('Os cupons deste parceiro estão desativados.');
            }

            $desconto = min($desconto, round($valor * $teto / 100, 2));
        }

        return [
            'cupom' => $cupom,
            'valor_original' => $valor,
            'desconto' => $desconto,
            'valor_final' => round($valor - $desconto, 2),
        ];
    }

    /**
     * O CPF já tem (ou teve, via cupom) contrato neste parceiro? Vínculos removidos
     * são apagados, então o histórico de usos de cupom completa a verificação.
     */
    private function jaContratou(string $tenantId, string $cpf, ?Model $vinculoAtual): bool
    {
        $exceto = fn (string $classe) => $vinculoAtual instanceof $classe ? $vinculoAtual->getKey() : null;

        $telemedicina = TelemedicinaTenant::where('tenant_id', $tenantId)
            ->when($exceto(TelemedicinaTenant::class), fn ($q, $id) => $q->whereKeyNot($id))
            ->get(['id', 'data'])
            ->contains(fn (TelemedicinaTenant $v) => $this->digitos($v->data['cpf_cnpj'] ?? null) === $cpf);

        return $telemedicina
            || TenantPlanoBeneficiario::where('tenant_id', $tenantId)
                ->where('cpf', $cpf)
                ->when($exceto(TenantPlanoBeneficiario::class), fn ($q, $id) => $q->whereKeyNot($id))
                ->exists()
            || CupomUso::where('tenant_id', $tenantId)->where('cpf', $cpf)->exists();
    }

    private function digitos(?string $valor): string
    {
        return preg_replace('/\D/', '', (string) $valor);
    }

    /**
     * @throws ValidationException
     */
    private function falhar(string $mensagem): never
    {
        throw ValidationException::withMessages(['cupom' => $mensagem]);
    }
}
