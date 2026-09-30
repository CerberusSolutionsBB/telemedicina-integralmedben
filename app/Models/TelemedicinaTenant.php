<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class TelemedicinaTenant extends Model implements Auditable
{
    use AuditableTrait;

    protected $connection = 'mysql';

    protected $table = 'telemedicina_tenant';

    protected $fillable = [
        'tenant_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Tag das auditorias: permite listar o histórico de um tenant
     * (new_values é texto e não dá para filtrar por JSON).
     */
    public function generateTags(): array
    {
        return ['tenant:'.$this->tenant_id];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
