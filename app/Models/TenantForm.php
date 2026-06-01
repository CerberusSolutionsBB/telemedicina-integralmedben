<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantForm extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql';

    public const ORIGEM_CENTRAL = 'CENTRAL';

    public const ORIGEM_CLIENTE = 'CLIENTE';

    protected $table = 'tenants_forms';

    protected $fillable = [
        'tenant_id',
        'form_id',
        'user_id',
        'origem',
        'ativo',
        'principal',
        'expires_at',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'principal' => 'boolean',
        'expires_at' => 'datetime',

    ];

    protected $appends = [
        'expires_at_br',
    ];

    public function getExpiresAtBrAttribute(): ?string
    {
        if (! $this->expires_at) {
            return null;
        }

        return Carbon::parse($this->expires_at)->format('d/m/Y H:i');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    public function scopePrincipal($query)
    {
        return $query->where('principal', true);
    }

    public function scopeOrigemCentral($query)
    {
        return $query->where('origem', self::ORIGEM_CENTRAL);
    }

    public function scopeOrigemCliente($query)
    {
        return $query->where('origem', self::ORIGEM_CLIENTE);
    }
}
