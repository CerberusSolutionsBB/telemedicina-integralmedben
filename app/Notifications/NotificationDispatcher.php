<?php

namespace App\Notifications;

use App\Enums\SmsStatusEnum;
use App\Interfaces\NotificationChannelInterface;
use App\Models\SmsLogs;
use App\Models\SmsTemplate;
use App\Models\Tenant;

class NotificationDispatcher
{
    /** @param NotificationChannelInterface[] $channels */
    public function __construct(private array $channels)
    {
    }

    public function send(SmsTemplate $template, array $data): void
    {
        $recipient = $data[$template->recipient_variable] ?? null;

        if (!$recipient) {
            return;
        }

        $tenantId = $data['tenant_id'] ?? null;
        $tenant   = $tenantId ? Tenant::find($tenantId) : null;
        $message  = $template->resolveMessage($data);

        if ($tenant && !$tenant->hasSmsQuota()) {
            SmsLogs::create([
                'tenant_id'     => $tenantId,
                'patient_id'    => $data['patient_id'] ?? null,
                'recipient'     => $recipient,
                'message'       => $message,
                'status'        => SmsStatusEnum::Failed,
                'error_message' => 'Cota de SMS esgotada.',
            ]);
            return;
        }

        $channel = $this->resolve($template->channel);

        $log = SmsLogs::create([
            'tenant_id'  => $tenantId,
            'patient_id' => $data['patient_id'] ?? null,
            'recipient'  => $recipient,
            'message'    => $message,
            'status'     => SmsStatusEnum::Pending,
        ]);

        try {
            $channel->send($recipient, $message);

            $log->update([
                'status'  => SmsStatusEnum::Sent,
                'sent_at' => now(),
            ]);

            $tenant?->decrementSmsQuota();
        } catch (\Throwable $e) {
            $log->update([
                'status'        => SmsStatusEnum::Failed,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function resolve(string $channelType): NotificationChannelInterface
    {
        foreach ($this->channels as $channel) {
            if ($channel->supports($channelType)) {
                return $channel;
            }
        }

        throw new \InvalidArgumentException("Canal de notificação '{$channelType}' não suportado.");
    }
}
