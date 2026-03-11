<?php

namespace App\Models;

use App\Enums\SmsStatusEnum;
use Illuminate\Database\Eloquent\Model;

class SmsLogs extends Model
{
    protected $connection = 'mysql';

    protected $table = 'sms_logs';

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'recipient',
        'message',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'status'  => SmsStatusEnum::class,
        'sent_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
