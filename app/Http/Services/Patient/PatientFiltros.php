<?php

namespace App\Http\Services\Patient;

use App\Models\Audit;
use App\Models\Patient;
use App\Models\TelemedicinaTenant;
use App\Models\TenantPlano;
use App\Models\TenantPlanoBeneficiario;
use App\Models\User;
use App\Services\Tenant\TenantPlanoCotaService;
use App\Support\Planos;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filtros da listagem de beneficiários, também usados pelo Relatório Geral:
 * busca, status, tipo de registro, plano e usuário que criou o cadastro.
 */
class PatientFiltros
{
    /** Valor do filtro de plano para quem não tem plano. */
    public const SEM_PLANO = 'sem';

    public const CHAVES = ['search', 'status', 'registro', 'plano', 'criado_por'];

    /**
     * @param  array{search?: ?string, status?: ?string, registro?: ?string, plano?: ?string, criado_por?: ?string}  $filtros
     */
    public static function aplicar(Builder $query, string $tenantId, array $filtros): Builder
    {
        $search = trim((string) ($filtros['search'] ?? ''));
        $status = $filtros['status'] ?? null;
        $registro = $filtros['registro'] ?? null;
        $plano = (string) ($filtros['plano'] ?? '');
        $criadoPor = (string) ($filtros['criado_por'] ?? '');

        return $query
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('nome', 'like', "%{$search}%")
                ->orWhere('cpf', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($status !== null && $status !== '', fn ($q) => $q->where('status', $status))
            ->when($registro !== null && $registro !== '', fn ($q) => $q->where('status_registro', $registro))
            ->when($plano !== '', function ($q) use ($tenantId, $plano) {
                $cpfs = self::formatosCpf(self::cpfsComPlano($tenantId, $plano === self::SEM_PLANO ? null : $plano));

                return $plano === self::SEM_PLANO
                    ? $q->where(fn ($q) => $q->whereNull('cpf')->orWhereNotIn('cpf', $cpfs))
                    : $q->whereIn('cpf', $cpfs);
            })
            ->when($criadoPor !== '', fn ($q) => $q->whereIn('id', self::criadosPor($tenantId, (int) $criadoPor)));
    }

    /**
     * Totalizadores da listagem por status e por plano. Respeitam os demais
     * filtros, mas não o próprio status/plano (os cards também são filtros).
     *
     * @return array{total: int, ativos: int, inativos: int, planos: array<int, array{value: string, label: string, total: int}>}
     */
    public static function totais(string $tenantId, array $filtros): array
    {
        $pacientes = self::aplicar(Patient::query(), $tenantId, array_diff_key($filtros, array_flip(['status', 'plano'])))
            ->get(['id', 'cpf', 'status']);

        $mapa = self::codigosPorCpf($tenantId);
        $porPlano = [];
        $semPlano = 0;

        foreach ($pacientes as $patient) {
            $codigos = $mapa[preg_replace('/\D/', '', (string) $patient->cpf)] ?? [];

            if (! $codigos) {
                $semPlano++;
            }

            foreach ($codigos as $codigo) {
                $porPlano[$codigo] = ($porPlano[$codigo] ?? 0) + 1;
            }
        }

        // Planos do parceiro (mesmo zerados) e qualquer outro em uso.
        $doParceiro = TenantPlano::where('tenant_id', $tenantId)->pluck('cod_plano')->map(fn ($c) => (string) $c)->all();

        $planos = collect(Planos::options())
            ->filter(fn ($p) => in_array($p['value'], $doParceiro, true) || isset($porPlano[$p['value']]))
            ->map(fn ($p) => ['value' => $p['value'], 'label' => $p['label'], 'total' => $porPlano[$p['value']] ?? 0])
            ->push(['value' => self::SEM_PLANO, 'label' => 'Sem plano', 'total' => $semPlano])
            ->values()
            ->all();

        $ativos = $pacientes->where('status', true)->count();

        return [
            'total' => $pacientes->count(),
            'ativos' => $ativos,
            'inativos' => $pacientes->count() - $ativos,
            'planos' => $planos,
        ];
    }

    /**
     * Opções dos filtros de plano e de usuário (só quem já criou beneficiários).
     *
     * @return array{planos: array<int, array{value: string, label: string}>, usuarios: array<int, array{value: string, label: string}>}
     */
    public static function opcoes(string $tenantId): array
    {
        $planos = collect(Planos::options())
            ->map(fn ($p) => ['value' => $p['value'], 'label' => $p['label']])
            ->push(['value' => self::SEM_PLANO, 'label' => 'Sem plano'])
            ->values()
            ->all();

        $userIds = self::auditoriasDeCriacao($tenantId)->whereNotNull('user_id')->distinct()->pluck('user_id');

        $usuarios = User::whereIn('id', $userIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => ['value' => (string) $u->id, 'label' => $u->name])
            ->all();

        return ['planos' => $planos, 'usuarios' => $usuarios];
    }

    /**
     * CPFs (só dígitos) com o plano informado; sem plano informado, com qualquer plano.
     *
     * @return array<int, string>
     */
    private static function cpfsComPlano(string $tenantId, ?string $codPlano): array
    {
        $daTelemedicina = TelemedicinaTenant::where('tenant_id', $tenantId)
            ->get(['data'])
            ->filter(fn ($v) => $codPlano === null
                || in_array($codPlano, TenantPlanoCotaService::codigosDoVinculo($v->data ?? []), true))
            ->map(fn ($v) => preg_replace('/\D/', '', (string) ($v->data['cpf_cnpj'] ?? '')));

        $internos = TenantPlanoBeneficiario::where('tenant_id', $tenantId)
            ->when($codPlano !== null, fn ($q) => $q->where('cod_plano', $codPlano))
            ->whereNotNull('cpf')
            ->pluck('cpf');

        return $daTelemedicina->merge($internos)->filter()->unique()->values()->all();
    }

    /**
     * CPF (só dígitos) => códigos de plano. Plano interno tem prioridade sobre a
     * telemedicina, como na coluna Plano (PacientePlanoService::planosPorCpf).
     *
     * @return array<string, array<int, string>>
     */
    private static function codigosPorCpf(string $tenantId): array
    {
        $mapa = [];

        TelemedicinaTenant::where('tenant_id', $tenantId)->orderBy('created_at')->get(['data'])
            ->each(function ($v) use (&$mapa) {
                $cpf = preg_replace('/\D/', '', (string) ($v->data['cpf_cnpj'] ?? ''));
                if ($cpf) {
                    $mapa[$cpf] = TenantPlanoCotaService::codigosDoVinculo($v->data ?? []);
                }
            });

        TenantPlanoBeneficiario::where('tenant_id', $tenantId)->whereNotNull('cpf')->orderBy('id')
            ->get(['cpf', 'cod_plano'])
            ->each(function ($v) use (&$mapa) {
                $mapa[$v->cpf] = [(string) $v->cod_plano];
            });

        return $mapa;
    }

    /**
     * CPF gravado no paciente com ou sem máscara.
     *
     * @param  array<int, string>  $digitos
     * @return array<int, string>
     */
    private static function formatosCpf(array $digitos): array
    {
        return collect($digitos)
            ->flatMap(fn ($c) => [$c, preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $c)])
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Ids dos pacientes criados pelo usuário (auditoria "created" do tenant).
     *
     * @return array<int, int>
     */
    private static function criadosPor(string $tenantId, int $userId): array
    {
        return self::auditoriasDeCriacao($tenantId)->where('user_id', $userId)->pluck('auditable_id')->all();
    }

    private static function auditoriasDeCriacao(string $tenantId)
    {
        return Audit::where('auditable_type', Patient::class)
            ->where('event', 'created')
            ->where('tags', 'tenant:'.$tenantId);
    }
}
