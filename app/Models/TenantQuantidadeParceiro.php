<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Extrato do saldo de vagas por tenant/plano. Cada linha é um movimento
 * (consumo, devolução ou ajuste) com o saldo resultante em `quantidade`.
 */
class TenantQuantidadeParceiro extends Model
{
    public const TIPO_CONSUMO = 'consumo';

    public const TIPO_DEVOLUCAO = 'devolucao';

    public const TIPO_AJUSTE = 'ajuste';

    // Contagem de registrados zerada manualmente: saldo volta ao contratado.
    public const TIPO_ZERAGEM = 'zeragem';

    protected $connection = 'mysql';

    protected $table = 'tenants_quantidade_parceiros';

    protected $fillable = [
        'tenant_id',
        'cod_plano',
        'parceiro_id',
        'telemedicina_tenant_id',
        'tipo',
        'variacao',
        'quantidade',
    ];

    protected $casts = [
        'parceiro_id' => 'integer',
        'variacao' => 'integer',
        'quantidade' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function telemedicinaTenant()
    {
        return $this->belongsTo(TelemedicinaTenant::class);
    }
}
