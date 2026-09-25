<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormField extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'form_id',
        'type',
        'label',
        'placeholder',
        'required',
        'options',
        'help_text',
        'order',
    ];

    protected $casts = [
        'required' => 'boolean',
        'options' => 'array',
        'order' => 'integer',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function isDateField(): bool
    {
        $label = strtolower($this->label);

        return $this->type === 'date' ||
            str_contains($label, 'nascimento') ||
            str_contains($label, 'data') ||
            str_contains($label, 'birth');
    }

    public function isCpfField(): bool
    {
        $label = strtolower($this->label);

        return $this->type === 'cpf' ||
            str_contains($label, 'cpf') ||
            str_contains($label, 'c.p.f');
    }

    // Campos "Número" (que não sejam CPF/data) são tratados como telefone celular/fixo
    public function isPhoneField(): bool
    {
        return $this->type === 'number' && ! $this->isCpfField() && ! $this->isDateField();
    }
}
