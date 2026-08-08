<?php

namespace App\Http\Controllers\Pagina;

use App\Enums\QuestionRoleEnum;
use App\Http\Controllers\Controller;
use App\Models\CentralPatient;
use App\Models\CentralPatientAnswer;
use App\Models\Form;
use App\Models\Question;
use App\Models\SmsTemplate;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantsDetail;
use App\Models\TenantForm;
use Illuminate\Support\Facades\Storage;
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

        $patients = $tenant->run(function () {
            return \App\Models\Patient::orderByDesc('created_at')
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'nome' => $p->nome,
                    'cpf' => $p->cpf,
                    'email' => $p->email,
                    'telefone' => $p->numero,
                    'sexo' => $p->sexo?->value ?? $p->sexo,
                    'data_nascimento' => $p->data_nascimento?->format('d/m/Y'),
                    'status' => (bool) $p->status,
                    'status_registro' => $p->status_registro?->value ?? null,
                    'created_at' => $p->created_at?->format('d/m/Y H:i'),
                ]);
        });

        $logo = null;
        if ($detail && $detail->logo) {
            $logoPath = $this->resolveLogoPath($tenant, $detail->logo);
            $logoExists = Storage::disk('tenants')->exists($logoPath);
            $dimensions = $logoExists ? @getimagesize(Storage::disk('tenants')->path($logoPath)) : false;
            $logo = [
                'id' => $detail->id,
                'url' => Storage::disk('tenants')->url($logoPath),
                'nome' => $detail->logo,
                'formato' => strtoupper(pathinfo($detail->logo, PATHINFO_EXTENSION)),
                'tamanho' => $logoExists ? Storage::disk('tenants')->size($logoPath) : null,
                'largura' => $dimensions[0] ?? null,
                'altura' => $dimensions[1] ?? null,
                'ativo' => true,
                'created_at' => $detail->created_at?->format('d/m/Y H:i'),
            ];
        }

        return Inertia::render('Pagina/Show', [
            'tenant' => $tenant,
            'forms' => $forms,
            'fomrs_tenants' => $fomrsTenants,
            'patients' => $patients,
            'arquivos' => $logo ? [$logo] : [],
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

    /**
     * Resolve o caminho real da logo no disco, considerando o esquema atual
     * (arquivo direto na raiz, nomeado pelo id do TenantsDetail) e os esquemas
     * antigos usados antes dele (pasta normalizada e pasta com o id cru do
     * tenant), para manter uploads antigos funcionando sem precisar migrá-los.
     */
    private function resolveLogoPath(Tenant $tenant, string $fileName): string
    {
        $candidates = [
            $fileName,
            $tenant->logoFolder().'/'.$fileName,
            $tenant->id.'/'.$fileName,
        ];

        foreach ($candidates as $candidate) {
            if (Storage::disk('tenants')->exists($candidate)) {
                return $candidate;
            }
        }

        return $fileName;
    }
}
