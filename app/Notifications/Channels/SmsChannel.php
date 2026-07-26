<?php

namespace App\Notifications\Channels;

use App\Interfaces\NotificationChannelInterface;
use App\Services\SimpleSmsService;

class SmsChannel implements NotificationChannelInterface
{
    public function __construct(private SimpleSmsService $smsService) {}

    public function send(string $recipient, string $message): void
    {
        $this->smsService->send($recipient, $message);
    }

    public function supports(string $channel): bool
    {
        return $channel === 'sms';
    }
}
