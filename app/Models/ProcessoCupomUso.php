<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uso de um cupom de benefício num processo (banco do tenant): quem usou, quando,
 * o código e os valores. Código e nome do usuário ficam guardados para o histórico
 * continuar legível se o cupom ou o usuário forem excluídos.
 */
class ProcessoCupomUso extends Model
{
    protected $table = 'processos_cupons_usos';

    protected $fillable = [
        'processo_id',
        'cupom_beneficio_id',
        'user_id',
        'usuario_nome',
        'codigo',
        'desconto_texto',
        'valor_original',
        'desconto',
        'valor_final',
    ];

    protected $casts = [
        'valor_original' => 'decimal:2',
        'desconto' => 'decimal:2',
        'valor_final' => 'decimal:2',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function cupom(): BelongsTo
    {
        return $this->belongsTo(CupomBeneficio::class, 'cupom_beneficio_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
