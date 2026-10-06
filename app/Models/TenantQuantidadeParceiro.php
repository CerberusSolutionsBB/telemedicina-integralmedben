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
        'plano_id',
        'user_id',
        'parceiro_id',
        'telemedicina_tenant_id',
        'tipo',
        'variacao',
        'quantidade',
        'valor',
    ];

    protected $casts = [
        'plano_id' => 'integer',
        'user_id' => 'integer',
        'parceiro_id' => 'integer',
        'variacao' => 'integer',
        'quantidade' => 'integer',
        'valor' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function plano()
    {
        return $this->belongsTo(TenantPlano::class, 'plano_id');
    }

    public function telemedicinaTenant()
    {
        return $this->belongsTo(TelemedicinaTenant::class);
    }
}
