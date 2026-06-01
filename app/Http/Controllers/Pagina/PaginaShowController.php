<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Tenant;
use App\Models\TenantForm;
use Inertia\Inertia;
use Inertia\Response;

class PaginaShowController extends Controller
{
    public function __invoke(Tenant $tenant): Response
    {
        $tenant = Tenant::with(['details', 'details.user', 'forms'])->where('id', $tenant->id)->firstOrFail();

        $forms = Form::orderBy('title', 'asc')->get();

        $fomrsTenants = TenantForm::with(['form'])->where('tenant_id', $tenant->id)->get();

        return Inertia::render('Pagina/Show', [
            'tenant' => $tenant,
            'forms' => $forms,
            'fomrs_tenants' => $fomrsTenants,
        ]);
    }
}
