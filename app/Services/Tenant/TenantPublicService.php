<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

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
            $logoPath = $tenant->id.'/'.basename($detail->logo);
            $logoUrl = $this->tenantAssetUrl($logoPath);
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

    private function tenantAssetUrl(string $path): string
    {
        $relativePath = parse_url(Storage::disk('tenants')->url($path), PHP_URL_PATH);

        return request()->getSchemeAndHttpHost().$relativePath;
    }
}
