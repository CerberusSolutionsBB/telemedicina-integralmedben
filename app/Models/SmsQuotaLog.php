<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsQuotaLog extends Model
{
    protected $connection = 'mysql';

    protected $table = 'sms_quota_logs';

    protected $fillable = [
        'tenant_id',
        'amount',
        'added_by',
        'notes',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by');
    }
}
