<?php

namespace App\Models;

use App\Enums\PatientSexoEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'central_patient_id',
        'nome',
        'cpf',
        'rg',
        'data_nascimento',
        'sexo',
        'email',
        'numero',
        'status',
        'enderecos',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'status' => 'boolean',
        'enderecos' => 'array',
        'sexo' => PatientSexoEnum::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function centralPatient(): BelongsTo
    {
        return $this->belongsTo(
            CentralPatient::class,
            'central_patient_id'
        );
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PatientAnswer::class);
    }
}
