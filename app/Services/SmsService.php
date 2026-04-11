<?php
namespace App\Services;

class SmsService
{
    private function send(string $message)
    {
        return ['message' => $message];
    }
}
