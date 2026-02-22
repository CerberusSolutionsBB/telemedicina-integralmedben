<?php

namespace App\Http\Services\CentralPatient;

use App\Models\CentralPatient;

class GetCentralPatientsService
{
    public function execute()
    {
        return CentralPatient::with(['answers.question', 'tenant'])
            ->latest()
            ->paginate(15);
    }
}
