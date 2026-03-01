<?php

namespace App\Listeners;

use App\Enums\SmsTemplateEventEnum;
use App\Events\PatientCreated;
use App\Models\Question;
use App\Models\SmsTemplate;
use App\Notifications\NotificationDispatcher;
use Illuminate\Support\Str;

class SendNotificationOnPatientCreated
{
    public function __construct(private NotificationDispatcher $dispatcher)
    {
    }

    public function handle(PatientCreated $event): void
    {
        // Busca templates ativos para o evento 'patient.created'
        // que estejam vinculados ao tenant via tabela pivot tenant_sms_templates
        $templates = SmsTemplate::where('event', SmsTemplateEventEnum::PatientCreated->value)
            ->where('is_active', true)
            ->whereHas('tenants', function ($query) use ($event) {
                $query->where('tenants.id', $event->tenantId);
            })
            ->get();

        if ($templates->isEmpty()) {
            return;
        }

        // Carrega as perguntas correspondentes às respostas do paciente
        $questions = Question::whereIn('id', array_keys($event->answers))
            ->get()
            ->keyBy('id');

        // Monta o array de variáveis disponíveis para o template.
        // Ex: ['telefone' => '11999999999', 'cpf' => '12345678900', 'nome_completo' => 'João']
        $data = [
            'tenant' => $event->tenantId, // nome do tenant (ex: "clinicaxyz")
        ];
        foreach ($event->answers as $questionId => $value) {
            $question = $questions[$questionId] ?? null;

            if (!$question) {
                continue;
            }

            // Perguntas com role especial usam o nome do role como chave.
            // Ex: pergunta com role='cpf' → $data['cpf'] = '12345678900'
            if ($question->role) {
                $data[$question->role->value] = $value;
            }

            // Todas as perguntas também ficam disponíveis pelo título slugificado.
            // Ex: "Nome Completo" → $data['nome_completo'] = 'João Silva'
            $key = Str::slug($question->title, '_');
            $data[$key] = $value;
        }

        // Dispara a notificação para cada template encontrado.
        // O Dispatcher decide se usa SMS, Email, etc. baseado em $template->channel.
        foreach ($templates as $template) {
            $this->dispatcher->send($template, $data);
        }
    }
}
