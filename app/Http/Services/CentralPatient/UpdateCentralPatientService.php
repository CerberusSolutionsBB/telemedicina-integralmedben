<?php

namespace App\Http\Services\CentralPatient;

use App\Models\CentralPatient;
use App\Models\CentralPatientAnswer;
use App\Models\Patient;
use App\Models\PatientAnswer;
use App\Models\Tenant;

class UpdateCentralPatientService
{
    public function execute(CentralPatient $centralPatient, array $answers): void
    {
        foreach ($answers as $questionId => $answer) {
            CentralPatientAnswer::updateOrCreate(
                [
                    'central_patient_id' => $centralPatient->id,
                    'question_id' => $questionId,
                ],
                ['answer' => $answer]
            );
        }

        $tenant = Tenant::find($centralPatient->tenant_id);

        if (!$tenant) {
            return;
        }

        $tenant->run(function () use ($centralPatient, $answers) {
            $patient = Patient::where('central_patient_id', $centralPatient->id)->first();

            if (!$patient) {
                return;
            }

            foreach ($answers as $questionId => $answer) {
                PatientAnswer::updateOrCreate(
                    [
                        'patient_id' => $patient->id,
                        'question_id' => $questionId,
                    ],
                    ['answer' => $answer]
                );
            }
        });
    }
}
