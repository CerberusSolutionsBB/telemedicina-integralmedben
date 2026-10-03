<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de membro da família no plano familiar (MAE, PAI...). Banco do tenant.
 */
class TipoVinculoFamiliar extends Model
{
    protected $table = 'tipos_vinculo_familiar';

    protected $fillable = [
        'codigo',
        'nome',
        'ordem',
    ];

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return self::orderBy('ordem')
            ->get(['codigo', 'nome'])
            ->map(fn (self $tipo) => ['value' => $tipo->codigo, 'label' => $tipo->nome])
            ->all();
    }
}
