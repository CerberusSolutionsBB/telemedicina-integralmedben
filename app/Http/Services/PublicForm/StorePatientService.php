<?php

namespace App\Http\Services\PublicForm;

use App\Enums\StatusRegistroEnum;
use App\Events\PatientCreated;
use App\Models\Patient;
use App\Models\PatientAnswer;

class StorePatientService
{
    public function execute(array $data)
    {
        $patient = Patient::create([
            'status_registro' => StatusRegistroEnum::FormPublico,
        ]);

        foreach ($data['answers'] as $questionId => $answer) {
            PatientAnswer::create([
                'patient_id' => $patient->id,
                'question_id' => $questionId,
                'answer' => $answer,
            ]);
        }

        PatientCreated::dispatch($patient->id, $data['tenant_id'], $data['answers']);

        return $patient;
    }
}
