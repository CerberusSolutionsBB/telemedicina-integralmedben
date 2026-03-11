<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsGlobalBalanceLog extends Model
{
    protected $connection = 'mysql';
    protected $table      = 'sms_global_balance_logs';
    protected $fillable   = [
        'amount',
        'type',
        'tenant_id',
        'added_by',
        'notes',
        'balance_after',
    ];

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
