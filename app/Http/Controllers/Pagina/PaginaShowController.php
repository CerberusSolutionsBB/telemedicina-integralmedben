<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\SmsTemplate;
use App\Models\Tenant;
use App\Models\TenantsDetail;
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

        $detail = TenantsDetail::where('tenant_id', $tenant->id)->first();
        $statusFormularioDinamico = $detail->configuracao['status_formulario_dinamico'] ?? false;

        return Inertia::render('Pagina/Show', [
            'tenant' => $tenant,
            'forms' => $forms,
            'fomrs_tenants' => $fomrsTenants,
            'statusFormularioDinamico' => $statusFormularioDinamico,
            'smsTemplates' => SmsTemplate::query()
                ->where('event', 'patient.created')
                ->orderByDesc('updated_at')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'message' => $t->message,
                    'event' => $t->event->value,
                    'plan_id' => $t->plan_id,
                    'variables' => $t->variables,
                    'is_active' => $t->is_active,
                    'created_at' => $t->created_at,
                    'updated_at' => $t->updated_at,
                ]),
        ]);
    }
}
