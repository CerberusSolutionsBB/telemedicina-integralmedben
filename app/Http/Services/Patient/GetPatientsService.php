<?php

namespace App\Http\Services\Patient;

use App\Models\Patient;

class GetPatientsService
{
    /**
     * @param  array{search?: ?string, status?: ?string, registro?: ?string, plano?: ?string, criado_por?: ?string}  $filtros
     */
    public function execute(array $filtros = [])
    {
        return PatientFiltros::aplicar(Patient::with('answers.question'), (string) tenant('id'), $filtros)
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }
}
