<?php

namespace App\Http\Services\CentralPatient;

use App\Models\CentralPatient;

class CentralPatientService
{
    public function __construct(
        private GetCentralPatientsService $getService,
        private UpdateCentralPatientService $updateService,
        private DeleteCentralPatientService $deleteService,
    ) {}

    public function getAll()
    {
        return $this->getService->execute();
    }

    public function update(CentralPatient $centralPatient, array $answers): void
    {
        $this->updateService->execute($centralPatient, $answers);
    }

    public function delete(CentralPatient $centralPatient): void
    {
        $this->deleteService->execute($centralPatient);
    }
}
