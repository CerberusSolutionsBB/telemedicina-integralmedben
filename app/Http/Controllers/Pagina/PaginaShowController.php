<?php

namespace App\Http\Controllers\Pagina;

use App\Enums\QuestionRoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Patient;
use App\Models\Question;
use App\Models\SmsTemplate;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantForm;
use App\Models\TenantPlano;
use App\Models\TenantsDetail;
use App\Services\Tenant\PlanoHistoricoService;
use App\Services\Tenant\TenantPlanoCotaService;
use App\Support\BeneficiarioPermissoes;
use App\Support\Planos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PaginaShowController extends Controller
{
    public function __invoke(Request $request, Tenant $tenant, TenantPlanoCotaService $planoCotaService, PlanoHistoricoService $planoHistorico): Response
    {
        $tenant = Tenant::with(['details', 'details.user', 'forms'])->where('id', $tenant->id)->firstOrFail();

        $forms = Form::orderBy('title', 'asc')->get();

        $fomrsTenants = TenantForm::with(['form'])->where('tenant_id', $tenant->id)->get();

        $detail = TenantsDetail::where('tenant_id', $tenant->id)->first();
        $statusFormularioDinamico = $detail->configuracao['status_formulario_dinamico'] ?? false;
        $telemedicinaEnabled = $detail->configuracao['telemedicina_enabled'] ?? false;
        $cartaoPacienteEnabled = $detail->configuracao['cartao_paciente_enabled'] ?? false;
        $cartaoDinamicoEnabled = $detail->configuracao['cartao_dinamico_enabled'] ?? false;

        $telemedicinaQuestions = Question::where('role', QuestionRoleEnum::Plan->value)->get();

        $telemedicinaVinculados = TelemedicinaTenant::where('tenant_id', $tenant->id)
            ->orderByDesc('updated_at')
            ->get();

        $patients = $tenant->run(function () {
            return Patient::orderByDesc('created_at')
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

        $cartaoDinamico = [
            'cor_primaria' => $detail?->cartao_cor_primaria,
            'cor_secundaria' => $detail?->cartao_cor_secundaria,
            'cor_texto' => $detail?->cartao_cor_texto,
            'fonte' => $detail?->cartao_fonte,
            'logo' => $this->resolveCartaoAsset($tenant, $detail, 'cartao_logo', 'logo'),
            'frente' => $this->resolveCartaoAsset($tenant, $detail, 'cartao_imagem_frente', 'frente'),
            'verso' => $this->resolveCartaoAsset($tenant, $detail, 'cartao_imagem_verso', 'verso'),
            'qrcode_habilitado' => (bool) ($detail?->cartao_qrcode_habilitado ?? false),
            'qrcode_dados' => $detail?->cartao_qrcode_dados ?? '',
            'verso_texto_info' => $detail?->cartao_verso_texto_info ?: 'Solicite atendimento 24h',
            'verso_rodape' => $detail?->cartao_verso_rodape ?: "Apresente este cartão nos locais conveniados\no obtenha benefícios especiais\nConsute o regulamento em nosso site:\nwww.integralmedben.com.br",
        ];

        $logo = null;
        if ($detail && $detail->logo) {
            $logoPath = $tenant->resolveLogoPath($detail->logo);
            $dimensions = $logoPath ? @getimagesize(Storage::disk('tenants')->path($logoPath)) : false;
            $logo = [
                'id' => $detail->id,
                'url' => route('pagina.configuracao.logo.show', $tenant->id).'?v='.urlencode($detail->logo),
                'nome' => $detail->logo,
                'formato' => strtoupper(pathinfo($detail->logo, PATHINFO_EXTENSION)),
                'tamanho' => $logoPath ? Storage::disk('tenants')->size($logoPath) : null,
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
            'cartaoPacienteEnabled' => $cartaoPacienteEnabled,
            'cartaoDinamicoEnabled' => $cartaoDinamicoEnabled,
            'cartaoDinamico' => $cartaoDinamico,
            'beneficiarioPermissoes' => BeneficiarioPermissoes::doTenant($tenant->id),
            'telemedicinaQuestions' => $telemedicinaQuestions,
            'telemedicinaVinculados' => $telemedicinaVinculados,
            'planos' => Planos::options(),
            'tenantPlanos' => TenantPlano::where('tenant_id', $tenant->id)
                ->get(['cod_plano', 'quantidade', 'valor']),
            'planoUso' => (object) $planoCotaService->uso($tenant->id),
            // Closure: no reload parcial dos filtros do histórico só ele é recalculado.
            'planoRegistros' => fn () => $planoHistorico->listar($tenant->id, $this->filtrosHistorico($request)),
            'planoRegistrosTotais' => fn () => $planoHistorico->totais($tenant->id, $this->filtrosHistorico($request), Planos::options()),
            'planoRegistrosFiltros' => $this->filtrosHistorico($request),
            'planoRegistrosLimite' => PlanoHistoricoService::LIMITE,
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
     * Filtros do histórico de registros dos planos (query string ?hist_*).
     */
    private function filtrosHistorico(Request $request): array
    {
        return [
            'busca' => (string) $request->query('hist_busca', ''),
            'plano' => (string) $request->query('hist_plano', ''),
            'de' => (string) $request->query('hist_de', ''),
            'ate' => (string) $request->query('hist_ate', ''),
        ];
    }

    private function resolveCartaoAsset(Tenant $tenant, ?TenantsDetail $detail, string $coluna, string $tipo): ?array
    {
        if (! $detail || ! $detail->{$coluna}) {
            return null;
        }

        $path = $tenant->resolveCartaoAssetPath($detail->{$coluna});

        return [
            'nome' => $detail->{$coluna},
            'url' => $path
                ? route('pagina.configuracao.cartao-dinamico.imagem.show', [$tenant->id, $tipo]).'?v='.urlencode($detail->{$coluna})
                : null,
        ];
    }
}
