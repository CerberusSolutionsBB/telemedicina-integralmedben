<?php

namespace App\Http\Services\SmsTemplate;

use App\Models\SmsTemplate;

class UpdateSmsTemplateService
{
    public function execute(SmsTemplate $template, array $data): void
    {
        $data['recipient_variable'] = match ($data['channel']) {
            'sms' => 'tel',
            default => 'tel',
        };

        preg_match_all('/\{(\w+)\}/', $data['message'], $matches);
        $data['variables'] = $matches[1];

        $template->update($data);
    }
}
