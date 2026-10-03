<?php

namespace App\Services\Siprov;

use App\Enums\QuestionRoleEnum;
use App\Models\CentralPatient;
use App\Models\Patient;
use App\Models\Question;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ao ativar/inativar o associado na SIPROV, replica o status no paciente
 * (beneficiário) dos parceiros onde o associado está vinculado à telemedicina.
 */
class SiprovStatusPacienteService
{
    /**
     * @return int quantidade de pacientes atualizados
     */
    public function sincronizar(string $cpf, bool $ativo): int
    {
        $digitos = preg_replace('/\D/', '', $cpf);

        if (strlen($digitos) !== 11) {
            return 0;
        }

        // CPF gravado com ou sem máscara.
        $formatos = array_values(array_unique([
            $digitos,
            preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digitos),
            $cpf,
        ]));

        $tenantIds = $this->tenantsDoCpf($digitos, $formatos);

        $atualizados = 0;

        foreach (Tenant::whereIn('id', $tenantIds)->get() as $tenant) {
            try {
                $atualizados += $tenant->run(fn () => Patient::whereIn('cpf', $formatos)
                    ->where('status', ! $ativo)
                    ->get()
                    // Um a um para a alteração ficar na auditoria do paciente.
                    ->each(fn (Patient $patient) => $patient->update(['status' => $ativo]))
                    ->count());
            } catch (Throwable $e) {
                Log::error('SIPROV | Falha ao replicar status no paciente do parceiro', [
                    'tenant_id' => $tenant->id,
                    'ativo' => $ativo,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $atualizados;
    }

    /**
     * Parceiros do associado: vínculos de telemedicina e, como na listagem da
     * SIPROV (AssociadosTenantParcenteService), o cadastro central pelo CPF.
     *
     * @param  array<int, string>  $formatos
     * @return array<int, string>
     */
    private function tenantsDoCpf(string $digitos, array $formatos): array
    {
        $daTelemedicina = TelemedicinaTenant::whereIn('data->cpf_cnpj', $formatos)->pluck('tenant_id');

        $cpfQuestion = Question::where('role', QuestionRoleEnum::Cpf)->first();

        $doCadastroCentral = $cpfQuestion
            ? CentralPatient::whereHas('answers', fn ($q) => $q
                ->where('question_id', $cpfQuestion->id)
                ->where('answer', $digitos))
                ->pluck('tenant_id')
            : collect();

        return $daTelemedicina->merge($doCadastroCentral)->filter()->unique()->values()->all();
    }
}
