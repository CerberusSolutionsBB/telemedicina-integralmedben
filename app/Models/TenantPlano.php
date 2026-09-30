<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class TenantPlano extends Model implements Auditable
{
    use AuditableTrait;

    protected $connection = 'mysql';

    protected $table = 'tenant_planos';

    protected $fillable = [
        'tenant_id',
        'cod_plano',
        'quantidade',
        'saldo',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'saldo' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
