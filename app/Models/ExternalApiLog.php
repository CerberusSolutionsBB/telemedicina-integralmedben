<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalApiLog extends Model
{
    protected $connection = 'mysql';
    protected $table      = 'external_api_logs';

    protected $fillable = [
        'api',
        'tenant_id',
        'patient_id',
        'status',
        'payload',
        'response',
        'error_message',
    ];

    protected $casts = [
        'payload'  => 'array',
        'response' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
