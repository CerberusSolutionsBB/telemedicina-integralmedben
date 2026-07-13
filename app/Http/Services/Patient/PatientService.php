<?php

namespace App\Http\Services\Patient;

use App\Models\Patient;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientService
{
    public function __construct(
        private GetPatientsService $getPatientsService,
        private GetPatientDetailsService $getPatientDetailsService,
        private StorePatientService $storePatientService,
        private UpdatePatientService $updatePatientService,
        private DeletePatientService $deletePatientService,
        private ExportPatientsService $exportPatientsService,
        private ImportPatientsService $importPatientsService,
    ) {}

    public function getPatients(?string $search = null, ?string $status = null, ?string $registro = null)
    {
        return $this->getPatientsService->execute($search, $status, $registro);
    }

    public function getPatientDetails(Patient $patient)
    {
        return $this->getPatientDetailsService->execute($patient);
    }

    public function store(array $data): Patient
    {
        return $this->storePatientService->execute($data);
    }

    public function update(Patient $patient, array $data): void
    {
        $this->updatePatientService->execute($patient, $data);
    }

    public function toggleStatus(Patient $patient): Patient
    {
        $patient->update(['status' => ! $patient->status]);

        return $patient->fresh();
    }

    public function delete(Patient $patient): void
    {
        $this->deletePatientService->execute($patient);
    }

    public function export(string $format = 'csv'): StreamedResponse
    {
        return $this->exportPatientsService->execute($format);
    }

    public function template($questions, string $format = 'csv'): StreamedResponse
    {
        return $this->exportPatientsService->generateTemplate($questions, $format);
    }

    public function import(array $data): array
    {
        return $this->importPatientsService->execute(
            $data['file'],
            $data['questions'],
        );
    }
}
