<?php

namespace App\Http\Services\Tenant;

use App\Models\Tenant;

class TenantDeleteService
{
    public function execute(string $tenant_id)
    {
        $tenant = Tenant::find($tenant_id);

        $tenant->domains()->delete();

        $tenant->delete();

        return $tenant;
    }
}
