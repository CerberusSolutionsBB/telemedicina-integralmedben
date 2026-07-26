<?php

namespace App\Notifications;

use App\Models\SmsTemplate;
use App\Services\SmsSenderService;

class NotificationDispatcher
{
    public function __construct(private SmsSenderService $smsSender) {}

    public function send(SmsTemplate $template, array $data): void
    {
        $recipient = $data[$template->recipient_variable] ?? null;

        if (! $recipient) {
            return;
        }

        $tenantId  = $data['tenant_id'] ?? null;
        $patientId = $data['patient_id'] ?? null;
        $message   = $template->resolveMessage($data);

        $this->smsSender->send($recipient, $message, $tenantId, $patientId);
    }
}
