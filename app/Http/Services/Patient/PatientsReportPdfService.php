<?php

namespace App\Http\Services\Patient;

use App\Models\Patient;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Models\TenantPlanoBeneficiario;
use App\Services\Tenant\TenantPlanoCotaService;
use App\Support\Formatar;
use App\Support\Planos;
use App\Support\RodapePdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Relatório geral de beneficiários em PDF (dompdf): resumo por status, plano e
 * origem + tabela com os dados de cada beneficiário, respeitando os filtros da lista.
 */
class PatientsReportPdfService
{
    // Linhas da tabela por página. A paginação é feita aqui porque o dompdf
    // ignora a margem inferior ao quebrar tabelas longas na primeira página.
    // A primeira página tem menos linhas por causa do cabeçalho e do resumo.
    public const LINHAS_PRIMEIRA_PAGINA = 18;

    public const LINHAS_POR_PAGINA = 28;

    public const ORIGENS = [
        'formulario' => 'Formulário',
        'form-dinamico' => 'Formulário dinâmico',
        'form-publico' => 'Formulário público',
        'importacao' => 'Importação',
        'vinculo' => 'Vínculo SIPROV',
    ];

    /**
     * @param  array{search?: ?string, status?: ?string, registro?: ?string, plano?: ?string, criado_por?: ?string}  $filtros
     */
    public function generate(string $tenantId, array $filtros = []): string
    {
        $tenant = Tenant::find($tenantId);
        $parceiro = $tenant?->name ?: ($tenant?->details()->first()?->descricao ?? $tenantId);
        $dados = $this->dados($tenantId, $filtros);

        $pdf = Pdf::loadView('pdf.relatorio-beneficiarios', [
            ...$dados,
            'tenant' => $tenant,
            'parceiro' => $parceiro,
            'logoBase64' => $this->logo($tenant),
        ])->setPaper('a4', 'landscape');

        return RodapePdf::aplicar(
            $pdf,
            'Gerado por '.($dados['gerado_por'] ?? 'sistema').' em '.$dados['gerado_em'],
            $parceiro.' · Relatório geral de beneficiários',
        )->output();
    }

    /**
     * Dados do relatório, já formatados para exibição.
     */
    public function dados(string $tenantId, array $filtros = []): array
    {
        $pacientes = $this->pacientes($tenantId, $filtros);
        $planoPorCpf = $this->planoPorCpf($tenantId);
        $labels = collect(Planos::options())->pluck('label', 'value');

        $linhas = $pacientes->map(function (Patient $patient) use ($planoPorCpf, $labels) {
            $codPlano = $planoPorCpf[Formatar::digitos($patient->cpf)] ?? null;
            $idade = Formatar::idade($patient->data_nascimento);
            $origem = $patient->status_registro?->value ?? $patient->status_registro;

            return [
                'id' => $patient->id,
                // Uma linha por beneficiário (altura previsível para a paginação).
                'nome' => $patient->nome ? Str::limit($patient->nome, 32, '…') : null,
                'cpf' => Formatar::cpf($patient->cpf),
                'nascimento' => Formatar::data($patient->data_nascimento),
                'idade' => $idade,
                'sexo' => Formatar::sexo($patient->sexo?->value ?? $patient->sexo),
                'celular' => Formatar::telefone($patient->numero),
                'email' => $patient->email ? Str::limit($patient->email, 30, '…') : null,
                'cod_plano' => $codPlano,
                'plano' => $codPlano ? ($labels[$codPlano] ?? "Plano {$codPlano}") : null,
                'origem_key' => $origem,
                'origem' => self::ORIGENS[$origem] ?? null,
                'ativo' => (bool) $patient->status,
                'cadastro' => Formatar::dataHora($patient->created_at),
            ];
        });

        return [
            'linhas' => $linhas->all(),
            'paginas' => $this->paginar($linhas->all()),
            'resumo' => $this->resumo($tenantId, $linhas, $labels),
            'filtros' => $this->descreverFiltros($tenantId, $filtros),
            'gerado_por' => auth()->user()?->name,
            'gerado_em' => Formatar::dataHora(now()),
        ];
    }

    /**
     * @return array<int, array<int, array>>
     */
    private function paginar(array $linhas): array
    {
        if (! $linhas) {
            return [];
        }

        return [
            array_slice($linhas, 0, self::LINHAS_PRIMEIRA_PAGINA),
            ...array_chunk(array_slice($linhas, self::LINHAS_PRIMEIRA_PAGINA), self::LINHAS_POR_PAGINA),
        ];
    }

    /**
     * Mesmos filtros da listagem (GetPatientsService), sem paginação.
     *
     * @return Collection<int, Patient>
     */
    private function pacientes(string $tenantId, array $filtros): Collection
    {
        return PatientFiltros::aplicar(Patient::query(), $tenantId, $filtros)
            ->orderBy('nome')
            ->get();
    }

    /**
     * CPF (só dígitos) → código do plano: planos internos + associados da telemedicina.
     *
     * @return array<string, string>
     */
    private function planoPorCpf(string $tenantId): array
    {
        $mapa = [];

        TelemedicinaTenant::where('tenant_id', $tenantId)->get(['data'])->each(function ($vinculo) use (&$mapa) {
            $cpf = Formatar::digitos($vinculo->data['cpf_cnpj'] ?? '');
            $codigo = TenantPlanoCotaService::codigosDoVinculo($vinculo->data ?? [])[0] ?? null;
            if ($cpf && $codigo) {
                $mapa[$cpf] = $codigo;
            }
        });

        TenantPlanoBeneficiario::where('tenant_id', $tenantId)->whereNotNull('cpf')
            ->get(['cpf', 'cod_plano'])
            ->each(function ($vinculo) use (&$mapa) {
                $mapa[$vinculo->cpf] = (string) $vinculo->cod_plano;
            });

        return $mapa;
    }

    private function resumo(string $tenantId, Collection $linhas, Collection $labels): array
    {
        $total = $linhas->count();
        $porPlano = $linhas->countBy(fn ($linha) => $linha['cod_plano'] ?? '');
        $configurados = TenantPlano::where('tenant_id', $tenantId)->pluck('cod_plano')->map(fn ($c) => (string) $c);

        // Planos habilitados no tenant (mesmo zerados) + os que aparecem nos dados.
        $planos = $configurados->merge($porPlano->keys()->filter())->unique()
            ->map(fn ($codigo) => ['label' => $labels[$codigo] ?? "Plano {$codigo}", 'total' => $porPlano[$codigo] ?? 0])
            ->values();

        $origens = collect(self::ORIGENS)
            ->map(fn ($label, $key) => ['label' => $label, 'total' => $linhas->where('origem_key', $key)->count()])
            ->filter(fn ($origem) => $origem['total'] > 0)
            ->values();

        return [
            'total' => $total,
            'ativos' => $linhas->where('ativo', true)->count(),
            'inativos' => $linhas->where('ativo', false)->count(),
            'planos' => $planos->push(['label' => 'Sem plano', 'total' => $porPlano[''] ?? 0])->all(),
            'origens' => $origens->all(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function descreverFiltros(string $tenantId, array $filtros): array
    {
        $descricao = [];

        if (filled($filtros['search'] ?? null)) {
            $descricao[] = 'Busca: "'.trim($filtros['search']).'"';
        }
        if (($filtros['status'] ?? '') !== '' && ($filtros['status'] ?? null) !== null) {
            $descricao[] = 'Status: '.($filtros['status'] ? 'Ativo' : 'Inativo');
        }
        if (filled($filtros['registro'] ?? null)) {
            $descricao[] = 'Origem: '.(self::ORIGENS[$filtros['registro']] ?? $filtros['registro']);
        }

        if (filled($filtros['plano'] ?? null) || filled($filtros['criado_por'] ?? null)) {
            $opcoes = PatientFiltros::opcoes($tenantId);
            $rotulo = fn (string $lista, string $valor) => collect($opcoes[$lista])->firstWhere('value', $valor)['label'] ?? $valor;

            if (filled($filtros['plano'] ?? null)) {
                $descricao[] = 'Plano: '.$rotulo('planos', (string) $filtros['plano']);
            }
            if (filled($filtros['criado_por'] ?? null)) {
                $descricao[] = 'Criado por: '.$rotulo('usuarios', (string) $filtros['criado_por']);
            }
        }

        return $descricao;
    }

    private function logo(?Tenant $tenant): ?string
    {
        if (! $tenant?->photo_path) {
            return null;
        }

        $caminho = base_path('storage/app/public/'.$tenant->photo_path);

        return file_exists($caminho)
            ? 'data:'.mime_content_type($caminho).';base64,'.base64_encode(file_get_contents($caminho))
            : null;
    }
}
