<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CentralPatient extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql';

    protected $table = 'central_patients';

    protected $fillable = [
        'cpf',
        'tenant_id',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CentralPatientAnswer::class);
    }
}
