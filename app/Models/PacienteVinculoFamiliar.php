<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Membro da família do beneficiário no plano familiar. Banco do tenant.
 */
class PacienteVinculoFamiliar extends Model
{
    protected $table = 'paciente_vinculo_familiares';

    protected $fillable = [
        'paciente_id',
        'plano_id',
        'nome',
        'cpf',
        'data_nascimento',
        'tipo',
    ];

    protected $casts = [
        'data_nascimento' => 'date:Y-m-d',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'paciente_id');
    }

    public function tipoVinculo(): BelongsTo
    {
        return $this->belongsTo(TipoVinculoFamiliar::class, 'tipo', 'codigo');
    }
}
