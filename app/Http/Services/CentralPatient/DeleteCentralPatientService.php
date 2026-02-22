<?php

namespace App\Http\Services\CentralPatient;

use App\Models\CentralPatient;

class DeleteCentralPatientService
{
    public function execute(CentralPatient $centralPatient): void
    {
        $centralPatient->delete();
    }
}
