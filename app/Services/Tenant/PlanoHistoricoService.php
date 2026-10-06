<?php

namespace App\Services\Tenant;

use App\Models\Audit;
use App\Support\Formatar;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Histórico de registros de pacientes nos planos (auditoria registro_plano) de
 * um tenant, com filtros por quem registrou/paciente, plano e período.
 */
class PlanoHistoricoService
{
    public const LIMITE = 100;

    // Varredura máxima: new_values é texto (acentos escapados), então busca e
    // plano são filtrados em PHP; o período é filtrado no SQL.
    private const VARREDURA = 2000;

    private const ORIGENS = [
        'cadastro_paciente' => 'Cadastro manual',
        'formulario_publico' => 'Formulário público',
        'sincronizacao_siprov' => 'Sincronização SIPROV',
    ];

    /**
     * @param  array{busca?: ?string, plano?: ?string, de?: ?string, ate?: ?string}  $filtros
     */
    public function listar(string $tenantId, array $filtros = []): array
    {
        $plano = (string) ($filtros['plano'] ?? '');

        return $this->filtrados($tenantId, $filtros)
            ->filter(fn (Audit $audit) => $plano === '' || $this->codPlano($audit) === $plano)
            ->take(self::LIMITE)
            ->map(fn (Audit $audit) => $this->formatar($audit))
            ->values()
            ->all();
    }

    /**
     * Total de registros por plano com os filtros de busca e período (o filtro de
     * plano não se aplica, para os cards dos outros planos não zerarem).
     * Conta todos os registros filtrados, não só os exibidos na tabela.
     *
     * @param  array<int, array{value: string, label: string}>  $planos  opções de plano (rótulos)
     * @return array{total: int, planos: array<int, array{cod_plano: string, plano: string, total: int}>}
     */
    public function totais(string $tenantId, array $filtros, array $planos): array
    {
        $porPlano = $this->filtrados($tenantId, $filtros)
            ->countBy(fn (Audit $audit) => $this->codPlano($audit));

        return [
            'total' => $porPlano->sum(),
            'planos' => collect($planos)
                ->map(fn (array $plano) => [
                    'cod_plano' => (string) $plano['value'],
                    'plano' => $plano['label'],
                    'total' => $porPlano[(string) $plano['value']] ?? 0,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Registros do tenant com busca (quem registrou/paciente) e período aplicados.
     *
     * @return Collection<int, Audit>
     */
    private function filtrados(string $tenantId, array $filtros)
    {
        $busca = $this->normalizar($filtros['busca'] ?? '');
        // Filtros digitados no horário de Brasília; o banco grava em UTC.
        $de = Formatar::localParaUtc($filtros['de'] ?? null);
        $ate = Formatar::localParaUtc($filtros['ate'] ?? null)?->endOfMinute();

        return Audit::where('event', 'registro_plano')
            ->where('tags', 'tenant:'.$tenantId)
            ->when($de, fn ($q) => $q->where('created_at', '>=', $de))
            ->when($ate, fn ($q) => $q->where('created_at', '<=', $ate))
            ->latest('id')
            ->limit(self::VARREDURA)
            ->get()
            ->filter(function (Audit $audit) use ($busca) {
                $dados = $audit->new_values ?? [];

                return $busca === ''
                    || str_contains($this->normalizar($dados['usuario'] ?? ''), $busca)
                    || str_contains($this->normalizar($dados['paciente'] ?? ''), $busca);
            });
    }

    private function codPlano(Audit $audit): string
    {
        return (string) ($audit->new_values['cod_plano'] ?? '');
    }

    private function formatar(Audit $audit): array
    {
        $dados = $audit->new_values ?? [];
        $plano = $dados['planos'][$dados['cod_plano'] ?? ''] ?? [];

        return [
            'id' => $audit->id,
            'data' => Formatar::dataHora($audit->created_at),
            'paciente' => $dados['paciente'] ?? (isset($dados['paciente_id']) ? "#{$dados['paciente_id']}" : '-'),
            'plano' => $dados['plano'] ?? '-',
            'origem' => self::ORIGENS[$dados['origem'] ?? ''] ?? ($dados['origem'] ?? '-'),
            'usuario' => $dados['usuario'] ?? null,
            'ip' => $audit->ip_address,
            'dispositivo' => $audit->dispositivo,
            'user_agent' => $audit->user_agent,
            'saldo' => $plano['saldo'] ?? null,
            'quantidade' => $plano['quantidade'] ?? null,
            'valor' => isset($dados['valor']) ? 'R$ '.number_format((float) $dados['valor'], 2, ',', '.') : null,
        ];
    }

    private function normalizar(?string $texto): string
    {
        return Str::lower(Str::ascii(trim((string) $texto)));
    }
}
