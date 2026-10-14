<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uso de um cupom numa venda, com o snapshot de valores do momento.
 * Estornado quando a vaga do beneficiário é devolvida.
 */
class CupomUso extends Model
{
    protected $connection = 'mysql';

    protected $table = 'cupom_usos';

    protected $fillable = [
        'cupom_id',
        'tenant_id',
        'cpf',
        'paciente_nome',
        'cod_plano',
        'origem_type',
        'origem_id',
        'valor_original',
        'desconto',
        'valor_final',
        'user_id',
        'usuario_nome',
        'ip',
        'estornado_em',
    ];

    protected $casts = [
        'valor_original' => 'decimal:2',
        'desconto' => 'decimal:2',
        'valor_final' => 'decimal:2',
        'estornado_em' => 'datetime',
    ];

    public function scopeValidos(Builder $query): Builder
    {
        return $query->whereNull('estornado_em');
    }

    public function cupom(): BelongsTo
    {
        return $this->belongsTo(Cupom::class);
    }
}
