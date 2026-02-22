<?php

namespace App\Http\Services\Patient;

use App\Models\Patient;

class PatientService
{
    public function __construct(
        private GetPatientsService $getPatientsService,
        private GetPatientDetailsService $getPatientDetailsService,
        private UpdatePatientService $updatePatientService,
        private DeletePatientService $deletePatientService,
    ) {}

    public function getPatients()
    {
        return $this->getPatientsService->execute();
    }

    public function getPatientDetails(Patient $patient)
    {
        return $this->getPatientDetailsService->execute($patient);
    }

    public function update(Patient $patient, array $answers): void
    {
        $this->updatePatientService->execute($patient, $answers);
    }

    public function delete(Patient $patient): void
    {
        $this->deletePatientService->execute($patient);
    }
}
