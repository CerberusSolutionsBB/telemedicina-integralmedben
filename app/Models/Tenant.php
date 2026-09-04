<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
        'status' => 'boolean',
    ];

    protected $appends = [
        'tenant_domain',
        'photo_url',
        'url',
    ];

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'tenant_questions');
    }

    public function smsTemplates()
    {
        return $this->belongsToMany(SmsTemplate::class, 'tenant_sms_templates');
    }

    public function details()
    {
        return $this->hasMany(TenantsDetail::class, 'tenant_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function centralPatients()
    {
        return $this->hasMany(CentralPatient::class, 'tenant_id');
    }

    public function smsLogs()
    {
        return $this->hasMany(SmsLogs::class, 'tenant_id');
    }

    public function hasSmsQuota(): bool
    {
        return $this->sms_quota > 0;
    }

    public function decrementSmsQuota(): void
    {
        if ($this->sms_quota > 0) {
            $this->decrement('sms_quota');
        }
    }

    public static function generateDatabaseName(string $tenantName): string
    {
        return 'tenant_'.str_replace('-', '_', $tenantName);
    }

    public function logoFolder(): string
    {
        return Str::slug($this->id, '_');
    }

    /**
     * Caminhos possíveis do arquivo de logo no disco 'tenants', do mais novo
     * para o mais antigo: arquivo direto na raiz (esquema atual), pasta
     * normalizada e pasta com o id cru do tenant (esquemas antigos).
     */
    public function logoPathCandidates(string $fileName): array
    {
        return array_unique([
            $fileName,
            $this->logoFolder().'/'.$fileName,
            $this->id.'/'.$fileName,
        ]);
    }

    public function resolveLogoPath(string $fileName): ?string
    {
        foreach ($this->logoPathCandidates($fileName) as $candidate) {
            if (Storage::disk('tenants')->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Mesma ideia de logoPathCandidates(), reaproveitada para as imagens do
     * módulo de Cartão Dinâmico (logo/frente/verso), que usam o mesmo disco
     * 'tenants' e o mesmo esquema de nomes únicos.
     */
    public function cartaoAssetPathCandidates(string $fileName): array
    {
        return $this->logoPathCandidates($fileName);
    }

    public function resolveCartaoAssetPath(string $fileName): ?string
    {
        foreach ($this->cartaoAssetPathCandidates($fileName) as $candidate) {
            if (Storage::disk('tenants')->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function getTenantDomainAttribute()
    {
        return $this->domains->first()?->domain;
    }

    public function getPhotoUrlAttribute()
    {
        if ($this->photo_path) {
            return '/storage/'.$this->photo_path;
        }

        return null;
    }

    public function getUrlAttribute()
    {
        if (! $this->tenant_domain) {
            return null;
        }

        $domain = $this->tenant_domain;

        if (str_contains($domain, 'localhost')) {
            return 'http://'.$domain.':8000';
        }

        return 'https://'.$domain;
    }

    public function forms()
    {
        return $this->belongsToMany(
            Form::class,
            'tenants_forms',
            'tenant_id',
            'form_id'
        )
            ->withTimestamps();
    }

    public function telemedicinaItems()
    {
        return $this->hasMany(TelemedicinaTenant::class, 'tenant_id', 'id');
    }
}
