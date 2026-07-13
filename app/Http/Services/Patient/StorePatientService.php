<?php

namespace App\Http\Services\Patient;

use App\Enums\StatusRegistroEnum;
use App\Events\PatientCreated;
use App\Models\FormsResponseTenent;
use App\Models\Patient;

class StorePatientService
{
    public function execute(array $data): Patient
    {
        $statusRegistro = ! empty($data['response_id'])
            ? StatusRegistroEnum::FormDinamico
            : StatusRegistroEnum::Formulario;

        $patient = Patient::create([
            'nome' => $data['nome'] ?? null,
            'cpf' => $data['cpf'] ?? null,
            'rg' => $data['rg'] ?? null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'email' => $data['email'] ?? null,
            'numero' => $data['numero'] ?? null,
            'enderecos' => $data['enderecos'] ?? null,
            'status' => $data['status'] ?? true,
            'status_registro' => $statusRegistro,
        ]);

        if (! empty($data['response_id'])) {
            FormsResponseTenent::where('response_id', $data['response_id'])
                ->update(['status_paciente' => true]);
        }

        PatientCreated::dispatch(
            tenantPatientId: $patient->id,
            tenantId: tenant('id'),
            answers: $data['answers'] ?? [],
        );

        return $patient;
    }
}
