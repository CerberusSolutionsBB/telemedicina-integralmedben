<?php

namespace App\Interfaces;

interface NotificationChannelInterface
{
    public function send(string $recipient, string $message): void;

    public function supports(string $channel): bool;
}
