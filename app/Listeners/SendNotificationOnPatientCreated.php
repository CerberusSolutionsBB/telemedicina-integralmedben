<?php

namespace App\Listeners;

use App\Enums\QuestionRoleEnum;
use App\Enums\SmsTemplateEventEnum;
use App\Events\PatientCreated;
use App\Models\Question;
use App\Models\SmsTemplate;
use App\Notifications\NotificationDispatcher;
use Illuminate\Support\Str;

class SendNotificationOnPatientCreated
{
    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(PatientCreated $event): void
    {
        $allTemplates = SmsTemplate::where('event', SmsTemplateEventEnum::PatientCreated->value)
            ->where('is_active', true)
            ->whereHas('tenants', function ($query) use ($event) {
                $query->where('tenants.id', $event->tenantId);
            })
            ->get();

        if ($allTemplates->isEmpty()) {
            return;
        }

        // Carrega as perguntas e monta o $data de variáveis
        $questions = Question::whereIn('id', array_keys($event->answers))
            ->get()
            ->keyBy('id');

        $data = [
            'tenant_id' => $event->tenantId,
            'patient_id' => $event->tenantPatientId,
            'tenant' => $event->tenantId,
        ];

        $patientPlan = null;

        foreach ($event->answers as $questionId => $value) {
            $question = $questions[$questionId] ?? null;

            if (! $question) {
                continue;
            }

            if ($question->role) {
                $data[$question->role->value] = $value;

                // Captura o plano selecionado para filtrar templates
                if ($question->role === QuestionRoleEnum::Plan) {
                    $patientPlan = $value;
                }
            }

            $key = Str::slug($question->title, '_');
            $data[$key] = $value;
        }

        // Filtra templates:
        // - plan_id null → template geral, sempre enviado
        // - plan_id X    → só enviado se o paciente selecionou o plano X
        $templates = $allTemplates->filter(
            fn ($template) => $template->plan_id === null || $template->plan_id === $patientPlan
        );

        foreach ($templates as $template) {
            $this->dispatcher->send($template, $data);
        }
    }
}
