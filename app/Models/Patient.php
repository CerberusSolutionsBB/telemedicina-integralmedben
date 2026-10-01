<?php

namespace App\Models;

use App\Enums\PatientSexoEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Patient extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

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
        'status_registro',
        'enderecos',
    ];

    protected $casts = [
        'data_nascimento' => 'date:Y-m-d',
        'status' => 'boolean',
        'status_registro' => \App\Enums\StatusRegistroEnum::class,
        'enderecos' => 'array',
        'sexo' => PatientSexoEnum::class,
    ];

    protected $appends = [
        'data_nascimento_formatada',
    ];

    /**
     * Tag das auditorias: ids de paciente se repetem entre tenants (cada um tem
     * seu banco), então a auditoria precisa do tenant para ser encontrada.
     */
    public function generateTags(): array
    {
        return tenant() ? ['tenant:'.tenant('id')] : [];
    }

    public function getDataNascimentoFormatadaAttribute(): ?string
    {
        return $this->data_nascimento?->format('d/m/Y');
    }

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
