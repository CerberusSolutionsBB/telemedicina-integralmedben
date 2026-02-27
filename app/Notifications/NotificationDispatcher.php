<?php

namespace App\Notifications;

use App\Interfaces\NotificationChannelInterface;
use App\Models\SmsTemplate;

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

        $message = $template->resolveMessage($data);
        $channel = $this->resolve($template->channel);
        $channel->send($recipient, $message);
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
