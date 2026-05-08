<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\TenantsDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TenantConfigurationService
{
    public function gerarTenantsDetail(Tenant $tenant): TenantsDetail
    {
        return DB::transaction(function () use ($tenant) {
            $slug = tenant_slug($tenant->name ?? $tenant->id);

            $relativePath = "tenants/{$slug}";
            $basePath = storage_path("app/{$relativePath}");

            $this->ensureTenantDirectories($basePath);

            return TenantsDetail::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                ],
                [
                    'code' => TenantsDetail::where('tenant_id', $tenant->id)->value('code')
                        ?? Str::upper(Str::random(8)),

                    'descricao' => $tenant->name ?? $tenant->id,
                    'slug' => Str::slug($tenant->name),
                    'path_arquivos' => $relativePath,
                    'user_id' => Auth::id(),

                    'logo' => null,
                    'favicon' => null,
                    'cor_primaria' => $tenant->bg_color ?? null,
                    'cor_secundaria' => $tenant->button_color ?? null,
                ]
            );
        });
    }



    private function ensureTenantDirectories(string $basePath): void
    {
        foreach (['logos', 'favicons', 'uploads'] as $directory) {
            File::ensureDirectoryExists(
                "{$basePath}/{$directory}",
                0755,
                true
            );
        }
    }
}
