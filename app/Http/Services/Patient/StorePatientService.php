<?php

namespace App\Http\Services\Patient;

use App\Models\Patient;

class StorePatientService
{
    public function execute(array $data): Patient
    {
        return Patient::create([
            'nome' => $data['nome'] ?? null,
            'cpf' => $data['cpf'] ?? null,
            'rg' => $data['rg'] ?? null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'email' => $data['email'] ?? null,
            'numero' => $data['numero'] ?? null,
            'enderecos' => $data['enderecos'] ?? null,
            'status' => true,
        ]);
    }
}
