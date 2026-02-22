<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'central_patient_id',
    ];

    public function centralPatient(): BelongsTo
    {
        return $this->belongsTo(CentralPatient::class, 'central_patient_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PatientAnswer::class);
    }
}
