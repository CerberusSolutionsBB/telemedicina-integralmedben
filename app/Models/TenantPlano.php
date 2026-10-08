<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class TenantPlano extends Model implements Auditable
{
    use AuditableTrait;

    public const COMISSAO_PERCENTUAL = 'percentual';

    public const COMISSAO_FIXO = 'fixo';

    public const COMISSOES = [
        self::COMISSAO_PERCENTUAL => 'Percentual do plano',
        self::COMISSAO_FIXO => 'Valor fixo por venda',
    ];

    protected $connection = 'mysql';

    protected $table = 'tenant_planos';

    protected $fillable = [
        'tenant_id',
        'cod_plano',
        'quantidade',
        'saldo',
        'valor',
        'comissao_tipo',
        'comissao_valor',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'saldo' => 'integer',
        'valor' => 'decimal:2',
        'comissao_valor' => 'decimal:2',
    ];

    /**
     * Comissão da venda deste plano, conforme o tipo configurado:
     * percentual sobre o valor do plano ou valor fixo por venda.
     */
    public function comissaoVenda(): float
    {
        if ($this->comissao_tipo === null || $this->comissao_valor === null) {
            return 0.0;
        }

        return $this->comissao_tipo === self::COMISSAO_FIXO
            ? round((float) $this->comissao_valor, 2)
            : round((float) ($this->valor ?? 0) * ((float) $this->comissao_valor / 100), 2);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
