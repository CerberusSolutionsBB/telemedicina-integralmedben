<?php

namespace App\Http\Services\Patient;

use App\Models\CentralPatientAnswer;
use App\Models\Patient;
use App\Models\PatientAnswer;

class UpdatePatientService
{
    public function execute(Patient $patient, array $data): void
    {
        $patientFields = [
            'nome', 'cpf', 'rg', 'data_nascimento', 'sexo',
            'email', 'numero', 'enderecos', 'status',
        ];

        $patientData = array_intersect_key($data, array_flip($patientFields));

        if (! empty($patientData)) {
            $patient->update($patientData);
        }

        if (isset($data['answers']) && is_array($data['answers'])) {
            foreach ($data['answers'] as $questionId => $answer) {
                PatientAnswer::updateOrCreate(
                    ['patient_id' => $patient->id, 'question_id' => $questionId],
                    ['answer' => $answer]
                );
            }

            if ($patient->central_patient_id) {
                foreach ($data['answers'] as $questionId => $answer) {
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
}
