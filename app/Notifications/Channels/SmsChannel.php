<?php

namespace App\Notifications\Channels;

use App\Http\Services\Sms\SmsService;
use App\Interfaces\NotificationChannelInterface;

class SmsChannel implements NotificationChannelInterface
{
    public function __construct(private SmsService $smsService)
    {
    }

    public function send(string $recipient, string $message): void
    {
        $this->smsService->send($recipient, $message);
    }

    public function supports(string $channel): bool
    {
        return $channel === 'sms';
    }
}
