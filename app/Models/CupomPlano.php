<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Plano em que o cupom vale (quando aplicavel_a = planos).
 */
class CupomPlano extends Model
{
    public $timestamps = false;

    protected $connection = 'mysql';

    protected $table = 'cupom_planos';

    protected $fillable = [
        'cupom_id',
        'cod_plano',
    ];
}
