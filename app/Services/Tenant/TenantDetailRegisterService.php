<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\TenantsDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TenantDetailRegisterService
{
    public function execute(array $data, Tenant $tenant): TenantsDetail
    {
        return DB::transaction(function () use ($data, $tenant) {
            $tenantId = $tenant->id;

            $relativePath = 'tenants/'.$tenantId;
            $basePath = storage_path('app/'.$relativePath);

            File::ensureDirectoryExists($basePath, 0755, true);
            File::ensureDirectoryExists($basePath.'/logos', 0755, true);
            File::ensureDirectoryExists($basePath.'/favicons', 0755, true);
            File::ensureDirectoryExists($basePath.'/uploads', 0755, true);

            return TenantsDetail::create([
                'code' => Str::upper(Str::random(8)),
                'descricao' => $data['descricao'],
                'slug' => Str::slug($data['descricao']),
                'path_arquivos' => $relativePath,
                'tenant_id' => $tenant->id,
                'user_id' => auth()->id(),
                'logo' => $data['logo'] ?? null,
                'favicon' => $data['favicon'] ?? null,
                'cor_primaria' => $data['cor_primaria'] ?? null,
                'cor_secundaria' => $data['cor_secundaria'] ?? null,
            ]);
        });
    }
}
