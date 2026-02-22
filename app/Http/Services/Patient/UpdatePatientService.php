<?php

namespace App\Http\Services\Patient;

use App\Models\CentralPatientAnswer;
use App\Models\Patient;
use App\Models\PatientAnswer;

class UpdatePatientService
{
    public function execute(Patient $patient, array $answers): void
    {
        foreach ($answers as $questionId => $answer) {
            PatientAnswer::updateOrCreate(
                ['patient_id' => $patient->id, 'question_id' => $questionId],
                ['answer' => $answer]
            );
        }

        if ($patient->central_patient_id) {
            foreach ($answers as $questionId => $answer) {
                CentralPatientAnswer::updateOrCreate(
                    [
                        'central_patient_id' => $patient->central_patient_id,
                        'question_id' => $questionId,
                    ],
                    ['answer' => $answer]
                );
            }
        }
    }
}
