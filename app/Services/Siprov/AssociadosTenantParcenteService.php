<?php

namespace App\Services\Siprov;

use App\Enums\QuestionRoleEnum;
use App\Models\CentralPatient;
use App\Models\CentralPatientAnswer;
use App\Models\Question;
use App\Models\Tenant;

class AssociadosTenantParcenteService
{
    public function AssociadosTenant(array $data): array
    {
        $cpfQuestion = Question::where('role', QuestionRoleEnum::Cpf)->first();

        if (! $cpfQuestion) {
            return $data;
        }

        $cpfs = collect($data)
            ->pluck('cpfCnpj')
            ->map(fn ($cpf) => preg_replace('/\D/', '', $cpf ?? ''))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($cpfs)) {
            return $data;
        }

        $answers = CentralPatientAnswer::where('question_id', $cpfQuestion->id)
            ->whereIn('answer', $cpfs)
            ->with('patient.tenant.details')
            ->get()
            ->groupBy('answer');

        return collect($data)->map(function ($associado) use ($answers) {
            $cpf = preg_replace('/\D/', '', $associado['cpfCnpj'] ?? '');

            $associado['tenants'] = [];

            if ($cpf && isset($answers[$cpf])) {
                $associado['tenants'] = $answers[$cpf]
                    ->map(fn (CentralPatientAnswer $answer) => $answer->patient?->tenant)
                    ->filter()
                    ->unique('id')
                    ->values()
                    ->toArray();
            }

            return $associado;
        })->toArray();
    }
}