<?php

namespace App\Services\Tenant;

use App\Models\Tenant;

class TenantPublicService
{
    public function current(): ?array
    {
        $host = request()->getHost();
        $currentTenant = str($host)->before('.')->toString();
        $tenant = Tenant::with(['details', 'domains'])
            ->where('id', $currentTenant)
            ->first();
        if (! $tenant) {
            return null;
        }
        $detail = $tenant->details->firstOrFail();
        $logoUrl = null;
        if ($detail->logo) {
            $logoUrl = route('pagina.configuracao.logo.show', $tenant->id);
        }

        return [
            'id' => $tenant->id,
            'descricao' => $detail->descricao ?? null,
            'slug' => $detail->slug,
            'sigla' => $detail->sigla,
            'domain' => $tenant->tenant_domain,
            'url' => $tenant->url,
            'logo' => $logoUrl,
        ];
    }
}
