<?php

namespace App\Services\Tenant;

use App\Models\PacienteVinculoFamiliar;
use App\Models\Patient;
use App\Support\Planos;
use Illuminate\Support\Facades\DB;

/**
 * Membros da família do beneficiário no plano familiar.
 */
class PacienteFamiliaresService
{
    public function __construct(
        private readonly PacientePlanoService $pacientePlanoService,
    ) {}

    /**
     * Plano familiar que vale para o paciente: o escolhido no formulário ou,
     * no Edit, o vínculo já existente. Null quando o plano não é familiar.
     */
    public function planoFamiliar(string $tenantId, ?string $codPlano, ?string $cpf): ?string
    {
        if ($codPlano) {
            return Planos::familiar($codPlano) ? $codPlano : null;
        }

        return ($this->pacientePlanoService->vinculoAtual($tenantId, $cpf)['familiar'] ?? false)
            ? (string) config('siprov.planos.clinica_familiar')
            : null;
    }

    /**
     * Grava a lista do formulário: atualiza os existentes (por id), cria os
     * novos e remove os que saíram da lista.
     *
     * @param  array<int, array{id?: ?int, nome: string, cpf?: ?string, data_nascimento?: ?string, sexo: string, tipo: string}>  $familiares
     * @return array<int, int> codDependente SIPROV dos familiares removidos (para inativar lá)
     */
    public function sincronizar(Patient $patient, string $planoId, array $familiares): array
    {
        return DB::transaction(function () use ($patient, $planoId, $familiares) {
            $mantidos = [];

            foreach ($familiares as $familiar) {
                $dados = [
                    'plano_id' => $planoId,
                    'nome' => $familiar['nome'],
                    'cpf' => preg_replace('/\D/', '', (string) ($familiar['cpf'] ?? '')) ?: null,
                    'data_nascimento' => $familiar['data_nascimento'] ?? null,
                    'sexo' => $familiar['sexo'],
                    'tipo' => $familiar['tipo'],
                ];

                $registro = ! empty($familiar['id'])
                    ? $patient->familiares()->find($familiar['id'])
                    : null;

                if ($registro) {
                    $registro->update($dados);
                } else {
                    $registro = $patient->familiares()->create($dados);
                }

                $mantidos[] = $registro->id;
            }

            $removidos = $patient->familiares()->whereNotIn('id', $mantidos);
            $codigos = (clone $removidos)->whereNotNull('siprov_cod_dependente')->pluck('siprov_cod_dependente')->map(fn ($c) => (int) $c)->all();
            $removidos->delete();

            return $codigos;
        });
    }

    /**
     * @return array<int, array{id: int, nome: string, cpf: string, data_nascimento: string, sexo: string, tipo: string}>
     */
    public function doPaciente(Patient $patient): array
    {
        return $patient->familiares()
            ->orderBy('id')
            ->get()
            ->map(fn (PacienteVinculoFamiliar $f) => [
                'id' => $f->id,
                'nome' => $f->nome,
                'cpf' => $f->cpf ?? '',
                'data_nascimento' => $f->data_nascimento?->format('Y-m-d') ?? '',
                'sexo' => $f->sexo ?? '',
                'tipo' => $f->tipo,
            ])
            ->all();
    }

    /**
     * Para a tela do beneficiário: com o nome do vínculo e a data formatada.
     *
     * @return array<int, array{id: int, nome: string, cpf: ?string, data_nascimento: ?string, sexo: ?string, tipo: string}>
     */
    public function paraExibicao(Patient $patient): array
    {
        return $patient->familiares()
            ->with('tipoVinculo')
            ->orderBy('id')
            ->get()
            ->map(fn (PacienteVinculoFamiliar $f) => [
                'id' => $f->id,
                'nome' => $f->nome,
                'cpf' => $f->cpf,
                'data_nascimento' => $f->data_nascimento?->format('d/m/Y'),
                'sexo' => $f->sexo,
                'tipo' => $f->tipoVinculo?->nome ?? $f->tipo,
            ])
            ->all();
    }
}
