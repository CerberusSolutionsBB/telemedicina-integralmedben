<?php

namespace App\Http\Controllers\Tenant\Form;

use App\Http\Controllers\Controller;
use App\Models\TenantForm;
use App\Models\TenantsDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $currentTenant = tenant();
        $tenantDetails = TenantsDetail::where('tenant_id', $currentTenant->id)
            ->first();
        $tenantForms = TenantForm::with('form')
            ->where('tenant_id', $currentTenant->id)
            ->ativo()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Tenant/Form/Index', [
            'tenant' => [
                'id' => $currentTenant->id,
                'name' => $currentTenant->name ?? null,
            ],
            'tenantDetails' => $tenantDetails,
            'tenantForms' => $tenantForms,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
            ],
            'statusOptions' => [
                ['value' => '', 'label' => 'Todos'],
                ['value' => 'ativo', 'label' => 'Ativo'],
                ['value' => 'rascunho', 'label' => 'Rascunho'],
                ['value' => 'pausado', 'label' => 'Pausado'],
                ['value' => 'encerrado', 'label' => 'Encerrado'],
            ],
        ]);
    }
}
