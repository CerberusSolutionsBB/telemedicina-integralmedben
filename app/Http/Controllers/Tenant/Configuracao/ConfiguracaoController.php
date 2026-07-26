<?php

namespace App\Http\Controllers\Tenant\Configuracao;

use App\Data\SiprovAssociadoQueryData;
use App\Enums\QuestionRoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpiresAtRequest;
use App\Http\Requests\TenantFormsRequest;
use App\Models\Question;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantForm;
use App\Models\TenantsDetail;
use App\Services\Siprov\SiprovAssociadoService;
use App\Services\Tenant\TenantConfigurationService;
use App\Services\Tenant\TenantFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracaoController extends Controller
{
    public function __construct(
        private readonly TenantConfigurationService $configuracaoService,
        private TenantFormService $tenantFormService,
        private readonly SiprovAssociadoService $siprovAssociadoService,
    ) {}

    private function tenantId(): string
    {
        return (string) tenant()->id;
    }

    private function logoPath(string $fileName): string
    {
        return $this->tenantId().'/'.$fileName;
    }

    /**
     * Gera URL usando o host atual do request (funciona com subdomínios)
     */
    private function tenantUrl(string $path): string
    {
        // Pega apenas o path relativo (ex: /storage/tenants/med_bem/logo.png)
        $relativePath = parse_url(Storage::disk('tenants')->url($path), PHP_URL_PATH);

        // Monta com o host atual do request (inclui subdomínio e porta)
        return request()->getSchemeAndHttpHost().$relativePath;
    }

    public function index(): Response
    {
        $tenantDetail = TenantsDetail::firstOrCreate([
            'tenant_id' => $this->tenantId(),
        ]);

        $logoUrl = null;
        if ($tenantDetail->logo) {
            $fileName = basename($tenantDetail->logo);
            $logoUrl = $this->tenantUrl(
                $this->logoPath($fileName)
            );
        }

        return Inertia::render('Tenant/Configuracao/Index', [
            'configurations' => [
                [
                    'key' => 'logo',
                    'label' => 'Logo do Sistema',
                    'description' => 'Imagem exibida no painel e telas públicas.',
                    'type' => 'image',
                    'icon' => 'image',
                    'category' => 'Aparência',
                    'value' => $logoUrl,
                    'updated_at' => $tenantDetail->updated_at?->format('d/m/Y H:i'),
                ],
            ],
        ]);
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $tenantDetail = TenantsDetail::firstOrCreate([
            'tenant_id' => $this->tenantId(),
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $fileName = 'logo_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

            $file->storeAs($this->tenantId(), $fileName, 'tenants');

            if ($tenantDetail->logo) {
                $oldFileName = basename($tenantDetail->logo);
                $oldPath = $this->logoPath($oldFileName);

                if (Storage::disk('tenants')->exists($oldPath)) {
                    Storage::disk('tenants')->delete($oldPath);
                }
            }

            $tenantDetail->update([
                'logo' => $fileName,
            ]);
        }

        return redirect()->back()->with('success', 'Logo atualizado com sucesso!');
    }

    public function detail(Request $request, Tenant $tenant)
    {
        try {

            $this->configuracaoService->gerarTenantsDetail($tenant);

            return redirect()
                ->route('pagina.show', $tenant->id)
                ->with('message', 'Configuração do tenant gerada com sucesso!')
                ->with('type', 'success');
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar configuração do tenant', [
                'message' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
            ]);

            return redirect()
                ->back()
                ->with('message', 'Não foi possível gerar as configurações do tenant.')
                ->with('type', 'error');
        }
    }

    public function createExpiresAt(
        ExpiresAtRequest $request,
        TenantForm $tenantForm
    ) {
        try {
            $validated = $request->validated();

            $tenantForm->update([
                'expires_at' => $validated['expires_at'] ?? null,
            ]);

            return redirect()
                ->route('pagina.show', [
                    'tenant' => $tenantForm->tenant_id,
                ])
                ->with('message', 'Data de expiração atualizada com sucesso.')
                ->with('type', 'success');

        } catch (\Throwable $e) {
            Log::error(
                'Erro ao atualizar data de expiração do formulário do tenant',
                [
                    'message' => $e->getMessage(),
                    'tenant_form_id' => $tenantForm->id ?? null,
                    'tenant_id' => $tenantForm->tenant_id ?? null,
                    'payload' => $request->all(),
                ]
            );

            return redirect()
                ->back()
                ->with('message', 'Não foi possível atualizar a data de expiração.')
                ->with('type', 'error');
        }
    }

    public function forms(TenantFormsRequest $request, Tenant $tenant)
    {
        try {
            $validated = $request->validated();
            $this->tenantFormService->sync(
                tenantId: $tenant->id,
                formIds: $validated['forms'] ?? [],
                extraData: [
                    'user_id' => auth()->id(),
                    'origem' => 'CENTRAL',
                    'ativo' => true,
                ]
            );

            return redirect()
                ->route('pagina.show', $tenant->id)
                ->with('message', 'Configuração do tenant gerada com sucesso!')
                ->with('type', 'success');
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar configuração do tenant', [
                'message' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
            ]);

            return redirect()
                ->back()
                ->with('message', 'Não foi possível gerar as configurações do tenant.')
                ->with('type', 'error');
        }
    }

    public function toggleStatusFormularioDinamico(Tenant $tenant)
    {
        try {
            $detail = TenantsDetail::firstOrCreate([
                'tenant_id' => $tenant->id,
            ]);

            $config = $detail->configuracao ?? [];
            $current = $config['status_formulario_dinamico'] ?? false;
            $config['status_formulario_dinamico'] = ! $current;
            $detail->update(['configuracao' => $config]);

            return redirect()
                ->route('pagina.show', $tenant->id)
                ->with('message', 'Status Formulário Dinâmico atualizado com sucesso.')
                ->with('type', 'success');
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar status_formulario_dinamico', [
                'tenant_id' => $tenant->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('message', 'Não foi possível atualizar o status.')
                ->with('type', 'error');
        }
    }

    public function syncTelemedicina(Request $request, Tenant $tenant)
    {
        try {
            $request->validate([
                'enabled' => ['boolean'],
                'questions' => ['array'],
                'questions.*' => ['integer', 'exists:questions,id'],
                'siprov_items' => ['array'],
                'siprov_items.*.codPessoa' => ['required'],
                'siprov_items.*.nomePessoa' => ['required', 'string'],
                'siprov_items.*.cpfCnpj' => ['nullable', 'string'],
                'siprov_items.*.planos' => ['nullable', 'array'],
                'siprov_items.*.codBeneficio' => ['nullable'],
            ]);

            $detail = TenantsDetail::firstOrCreate([
                'tenant_id' => $tenant->id,
            ]);

            $config = $detail->configuracao ?? [];
            $config['telemedicina_enabled'] = $request->boolean('enabled');
            $detail->update(['configuracao' => $config]);

            if ($request->boolean('enabled') && !empty($request->input('questions'))) {
                $questions = Question::whereIn('id', $request->input('questions'))->get();

                $existingIds = TelemedicinaTenant::where('tenant_id', $tenant->id)
                    ->pluck('data->question_id')
                    ->toArray();

                foreach ($questions as $question) {
                    if (in_array($question->id, $existingIds)) {
                        continue;
                    }

                    TelemedicinaTenant::create([
                        'tenant_id' => $tenant->id,
                        'data' => [
                            'question_id' => $question->id,
                            'title' => $question->title,
                            'type' => $question->type,
                            'options' => $question->options,
                        ],
                    ]);
                }
            }

            if (!empty($request->input('siprov_items'))) {
                foreach ($request->input('siprov_items') as $item) {
                    $planos = $item['planos'] ?? [];
                    $primeiroPlano = !empty($planos) ? $planos[0] : [];

                    TelemedicinaTenant::create([
                        'tenant_id' => $tenant->id,
                        'data' => [
                            'siprov_id' => $item['codPessoa'] ?? null,
                            'title' => $item['nomePessoa'] ?? '',
                            'cpf_cnpj' => $item['cpfCnpj'] ?? '',
                            'cod_plano' => $primeiroPlano['codPlano'] ?? null,
                            'plano_label' => $primeiroPlano['nome'] ?? '',
                            'codigo_integracao' => $item['codPessoa'] ?? null,
                            'codBeneficio' => $item['codBeneficio'] ?? null,
                        ],
                    ]);
                }
            }

            return redirect()
                ->route('pagina.show', $tenant->id)
                ->with('message', 'Configuração de telemedicina atualizada com sucesso.')
                ->with('type', 'success');
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar telemedicina', [
                'tenant_id' => $tenant->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('message', 'Não foi possível atualizar a configuração de telemedicina.')
                ->with('type', 'error');
        }
    }

    public function unlinkTelemedicina(Tenant $tenant, TelemedicinaTenant $telemedicinaTenant)
    {
        try {
            $telemedicinaTenant->delete();

            return redirect()
                ->route('pagina.show', $tenant->id)
                ->with('message', 'Item de telemedicina desvinculado com sucesso.')
                ->with('type', 'success');
        } catch (\Throwable $e) {
            Log::error('Erro ao desvincular telemedicina', [
                'tenant_id' => $tenant->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('message', 'Não foi possível desvincular o item.')
                ->with('type', 'error');
        }
    }

    public function searchSiprov(Request $request)
    {
        $query = $request->input('q', '');
        $pagina = (int) $request->input('pagina', 1);

        $dto = new SiprovAssociadoQueryData(
            situacaoBeneficio: 'Ativo',
            nomePessoa: $query ?: null,
            pagina: $pagina > 1 ? $pagina : null,
        );

        try {
            $response = $this->siprovAssociadoService->query($dto);

            return response()->json([
                'itens' => $response['itens'] ?? [],
                'paginaAtual' => $response['paginaAtual'] ?? 1,
                'proximaPagina' => $response['proximaPagina'] ?? false,
                'quantidade' => $response['quantidade'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao buscar associados SIPROV no modal', [
                'query' => $query,
                'pagina' => $pagina,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'itens' => [],
                'paginaAtual' => 1,
                'proximaPagina' => false,
                'quantidade' => 0,
            ], 500);
        }
    }

    public function removeVinculo(TenantForm $tenantForm)
    {
        try {
            $tenantId = $tenantForm->tenant_id;

            $tenantForm->delete();

            return redirect()
                ->route('pagina.show', [
                    'tenant' => $tenantId,
                ])
                ->with('message', 'Formulário desvinculado com sucesso.')
                ->with('type', 'success');

        } catch (\Throwable $e) {
            Log::error('Erro ao desvincular formulário do tenant', [
                'message' => $e->getMessage(),
                'tenant_form_id' => $tenantForm->id ?? null,
                'tenant_id' => $tenantForm->tenant_id ?? null,
            ]);

            return redirect()
                ->back()
                ->with('message', 'Não foi possível desvincular o formulário.')
                ->with('type', 'error');
        }
    }
}
