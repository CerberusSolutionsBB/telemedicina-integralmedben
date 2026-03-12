<?php

namespace App\Listeners;

use App\Enums\QuestionRoleEnum;
use App\Events\PatientCreated;
use App\Http\Services\ExternalApi\ClubeBeneficiosService;
use App\Http\Services\ExternalApi\TelemedicinaService;
use App\Models\ExternalApiLog;
use App\Models\Question;
use App\Models\Tenant;
use Illuminate\Support\Str;

class RegisterPatientToExternalApis
{
    public function __construct(
        private ClubeBeneficiosService $clubeService,
        private TelemedicinaService    $telemedicinaService,
    ) {}

    public function handle(PatientCreated $event): void
    {
        $tenant = Tenant::find($event->tenantId);

        if (!$tenant) {
            return;
        }

        // Monta o payload normalizado a partir das respostas + roles das perguntas
        $payload = $this->buildPayload($event);

        // Sempre registra no clube de benefícios
        $this->call('clube_beneficios', $this->clubeService, $event, $payload);

        // Registra na telemedicina apenas se:
        // 1. O tenant tem a pergunta de plano configurada
        // 2. O paciente respondeu a pergunta de plano (selecionou um plano)
        $hasPlanQuestion = $tenant->questions()
            ->where('role', QuestionRoleEnum::Plan->value)
            ->exists();

        if ($hasPlanQuestion && !empty($payload['plan'])) {
            $this->call('telemedicina', $this->telemedicinaService, $event, $payload);
        }
    }

    /**
     * Normaliza as respostas usando o role da pergunta como chave.
     * Ex: ['cpf' => '12345678900', 'tel' => '11999999999', 'plan' => '42']
     */
    private function buildPayload(PatientCreated $event): array
    {
        $questions = Question::whereIn('id', array_keys($event->answers))
            ->get()
            ->keyBy('id');

        $payload = [
            'tenant_id'  => $event->tenantId,
            'patient_id' => $event->tenantPatientId,
        ];

        foreach ($event->answers as $questionId => $value) {
            $question = $questions[$questionId] ?? null;

            if (!$question) {
                continue;
            }

            // Usa o role como chave quando disponível (cpf, tel, plan)
            if ($question->role) {
                $payload[$question->role->value] = $value;
            }

            // Também indexa pelo slug do título para flexibilidade
            $payload[Str::slug($question->title, '_')] = $value;
        }

        return $payload;
    }

    /**
     * Executa a chamada para uma API e persiste o log do resultado.
     */
    private function call(
        string               $apiName,
        \App\Interfaces\ExternalApiInterface $service,
        PatientCreated       $event,
        array                $payload,
    ): void {
        try {
            $response = $service->registerPatient($payload);

            ExternalApiLog::create([
                'api'        => $apiName,
                'tenant_id'  => $event->tenantId,
                'patient_id' => $event->tenantPatientId,
                'status'     => 'success',
                'payload'    => $payload,
                'response'   => $response,
            ]);
        } catch (\Throwable $e) {
            ExternalApiLog::create([
                'api'           => $apiName,
                'tenant_id'     => $event->tenantId,
                'patient_id'    => $event->tenantPatientId,
                'status'        => 'failed',
                'payload'       => $payload,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
