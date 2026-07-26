<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelemedicinaTenant extends Model
{
    protected $connection = 'mysql';

    protected $table = 'telemedicina_tenant';

    protected $fillable = [
        'tenant_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
