<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Beneficiário vinculado a um plano interno (próprio do sistema, sem SIPROV).
 * É o equivalente do TelemedicinaTenant para esses planos.
 */
class TenantPlanoBeneficiario extends Model implements Auditable
{
    use AuditableTrait;

    protected $connection = 'mysql';

    protected $table = 'tenant_plano_beneficiarios';

    protected $fillable = [
        'tenant_id',
        'cod_plano',
        'patient_id',
        'nome',
        'cpf',
    ];

    /**
     * Mesma tag do TelemedicinaTenant: entra no histórico de registros do tenant.
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
