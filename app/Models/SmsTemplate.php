<?php

namespace App\Models;

use App\Enums\SmsTemplateEventEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SmsTemplate extends Model
{
    protected $connection = 'mysql';

    protected $table = 'sms_templates';

    protected $fillable = [
        'name',
        'message',
        'channel',
        'event',
        'plan_id',
        'recipient_variable',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'event' => SmsTemplateEventEnum::class,
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_sms_templates');
    }

    public function resolveMessage(array $data): string
    {
        $message = $this->message;

        foreach ($data as $key => $value) {
            $message = str_replace('{'.$key.'}', $value, $message);
        }

        return $message;
    }
}
