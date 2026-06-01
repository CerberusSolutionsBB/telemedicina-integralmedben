<?php

namespace App\Http\Services\SmsTemplate;

use App\Models\SmsTemplate;

class StoreSmsTemplateService
{
    public function execute(array $data): SmsTemplate
    {
        $data['recipient_variable'] = match ($data['channel']) {
            'sms' => 'tel',
            default => 'tel',
        };

        preg_match_all('/\{(\w+)\}/', $data['message'], $matches);
        $data['variables'] = $matches[1];

        return SmsTemplate::create($data);
    }
}
