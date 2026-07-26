<?php

namespace App\Http\Controllers\Pagina;

use App\Enums\QuestionRoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Question;
use App\Models\SmsTemplate;
use App\Models\TelemedicinaTenant;
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
        $telemedicinaEnabled = $detail->configuracao['telemedicina_enabled'] ?? false;

        $telemedicinaQuestions = Question::where('role', QuestionRoleEnum::Plan->value)->get();

        $telemedicinaVinculados = TelemedicinaTenant::where('tenant_id', $tenant->id)
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Pagina/Show', [
            'tenant' => $tenant,
            'forms' => $forms,
            'fomrs_tenants' => $fomrsTenants,
            'statusFormularioDinamico' => $statusFormularioDinamico,
            'telemedicinaEnabled' => $telemedicinaEnabled,
            'telemedicinaQuestions' => $telemedicinaQuestions,
            'telemedicinaVinculados' => $telemedicinaVinculados,
            'allTenants' => Tenant::whereNull('deleted_at')
                ->with(['details', 'forms'])
                ->orderBy('id')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->details->first()?->descricao ?? ($t->tenant_domain ?? $t->id),
                    'subdomain' => $t->tenant_domain,
                    'forms' => $t->forms->map(fn ($f) => [
                        'id' => $f->id,
                        'title' => $f->title,
                    ])->values()->toArray(),
                ])
                ->values()
                ->toArray(),
            'smsTemplates' => SmsTemplate::query()
                ->with('tenants')
                ->where('event', 'patient.created')
                ->orderByDesc('updated_at')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'message' => $t->message,
                    'event' => $t->event->value,
                    'plan_id' => $t->plan_id,
                    'form_ids' => $t->form_ids,
                    'variables' => $t->variables,
                    'is_active' => $t->is_active,
                    'created_at' => $t->created_at,
                    'updated_at' => $t->updated_at,
                    'tenants' => $t->tenants->pluck('id')->toArray(),
                ]),
        ]);
    }
}
