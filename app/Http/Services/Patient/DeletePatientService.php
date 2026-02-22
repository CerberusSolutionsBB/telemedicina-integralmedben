<?php

namespace App\Http\Services\Patient;

use App\Models\Patient;

class DeletePatientService
{
    public function execute(Patient $patient): void
    {
        $patient->delete();
    }
}
