<?php

namespace App\Listeners;

use App\Enums\QuestionRoleEnum;
use App\Events\PatientCreated;
use App\Models\CentralPatient;
use App\Models\CentralPatientAnswer;
use App\Models\Patient;
use App\Models\Question;
class SyncPatientToCentral
{
    public function handle(PatientCreated $event): void
    {
        $cpfQuestionId = Question::whereIn('id', array_keys($event->answers))
            ->where('role', QuestionRoleEnum::Cpf->value)
            ->value('id');

        $cpf = $cpfQuestionId
            ? preg_replace('/\D/', '', $event->answers[(string) $cpfQuestionId] ?? '')
            : null;

        if (!$cpf) {
            return;
        }

        // Busca pelo CPF normalizado (apenas dígitos) direto na tabela de respostas
        $cpfAnswer = CentralPatientAnswer::where('question_id', $cpfQuestionId)
            ->where('answer', $cpf)
            ->first();

        $centralPatient = $cpfAnswer
            ? CentralPatient::find($cpfAnswer->central_patient_id)
            : CentralPatient::create(['tenant_id' => $event->tenantId]);

        foreach ($event->answers as $questionId => $answer) {
            // CPF é sempre salvo normalizado (só dígitos) para garantir consistência nas buscas
            $storedAnswer = ((int) $questionId === (int) $cpfQuestionId) ? $cpf : $answer;

            CentralPatientAnswer::updateOrCreate(
                ['central_patient_id' => $centralPatient->id, 'question_id' => $questionId],
                ['answer' => $storedAnswer]
            );
        }

        Patient::where('id', $event->tenantPatientId)
            ->update(['central_patient_id' => $centralPatient->id]);
    }
}
