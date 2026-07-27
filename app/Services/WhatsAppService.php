<?php
namespace App\Services;

class WhatsAppService
{
    private function send(string $message)
    {
        return ['message' => $message];
    }
}
