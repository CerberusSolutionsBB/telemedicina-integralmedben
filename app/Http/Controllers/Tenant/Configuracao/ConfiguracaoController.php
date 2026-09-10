<?php

namespace App\Http\Controllers\Tenant\Configuracao;

use App\Data\SiprovAssociadoQueryData;
use App\Enums\QuestionRoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pagina\PaginaCartaoDinamicoController;
use App\Http\Requests\ExpiresAtRequest;
use App\Http\Requests\TenantFormsRequest;
use App\Models\CentralPatient;
use App\Models\CentralPatientAnswer;
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
    private const CARTAO_TIPO_COLUNA = [
        'logo' => 'cartao_logo',
        'frente' => 'cartao_imagem_frente',
        'verso' => 'cartao_imagem_verso',
    ];

    public function __construct(
        private readonly TenantConfigurationService $configuracaoService,
        private TenantFormService $tenantFormService,
        private readonly SiprovAssociadoService $siprovAssociadoService,
    ) {}

    private function tenantId(): string
    {
        return (string) tenant()->id;
    }

    public function index(): Response
    {
        $tenantId = $this->tenantId();

        $tenantDetail = TenantsDetail::firstOrCreate([
            'tenant_id' => $tenantId,
        ]);

        $logoUrl = null;
        if ($tenantDetail->logo) {
            $logoUrl = route('pagina.configuracao.logo.show', $tenantId).'?v='.urlencode($tenantDetail->logo);
        }

        $cartaoDinamicoEnabled = (bool) ($tenantDetail->configuracao['cartao_dinamico_enabled'] ?? false);
        $atualizadoEm = $tenantDetail->updated_at?->format('d/m/Y H:i');

        $configurations = [
            [
                'key' => 'logo',
                'label' => 'Logo do Sistema',
                'description' => 'Imagem exibida no painel e telas públicas.',
                'type' => 'image',
                'icon' => 'image',
                'category' => 'Aparência',
                'value' => $logoUrl,
                'updated_at' => $atualizadoEm,
                'upload_route' => 'configuracao.logo.update',
                'upload_mode' => 'inertia',
            ],
        ];

        // As configurações de Cartão Dinâmico só aparecem aqui depois que o
        // recurso é habilitado no painel central (Pagina/Show, aba de
        // configuração). Antes disso, não há o que customizar.
        if ($cartaoDinamicoEnabled) {
            array_push(
                $configurations,
                [
                    'key' => 'cartao_estilo',
                    'label' => 'Estilo do Cartão Dinâmico',
                    'description' => 'Cores de fundo (gradiente usado quando não houver imagem de frente/verso), cor do texto e fonte.',
                    'type' => 'style',
                    'category' => 'Cartão Dinâmico',
                    'value' => [
                        'cor_primaria' => $tenantDetail->cartao_cor_primaria ?: '#22d3ee',
                        'cor_secundaria' => $tenantDetail->cartao_cor_secundaria ?: '#0e7490',
                        'cor_texto' => $tenantDetail->cartao_cor_texto ?: '#ffffff',
                        'fonte' => $tenantDetail->cartao_fonte ?: 'sans-serif',
                    ],
                    'save_route' => 'configuracao.cartao-dinamico.cores',
                ],
                [
                    'key' => 'cartao_logo',
                    'label' => 'Logo do Cartão Dinâmico',
                    'description' => 'Logo exibida na frente e no verso do cartão dinâmico.',
                    'type' => 'image',
                    'category' => 'Cartão Dinâmico',
                    'value' => $this->cartaoImagemUrl($tenantId, 'logo', $tenantDetail->cartao_logo),
                    'updated_at' => $atualizadoEm,
                    'upload_route' => 'configuracao.cartao-dinamico.imagem.store',
                    'upload_route_params' => ['logo'],
                    'delete_route' => 'configuracao.cartao-dinamico.imagem.destroy',
                    'delete_route_params' => ['logo'],
                    'upload_mode' => 'fetch',
                ],
                [
                    'key' => 'cartao_imagem_frente',
                    'label' => 'Imagem de Frente',
                    'description' => 'Fundo da frente do cartão dinâmico.',
                    'type' => 'image',
                    'category' => 'Cartão Dinâmico',
                    'value' => $this->cartaoImagemUrl($tenantId, 'frente', $tenantDetail->cartao_imagem_frente),
                    'updated_at' => $atualizadoEm,
                    'upload_route' => 'configuracao.cartao-dinamico.imagem.store',
                    'upload_route_params' => ['frente'],
                    'delete_route' => 'configuracao.cartao-dinamico.imagem.destroy',
                    'delete_route_params' => ['frente'],
                    'upload_mode' => 'fetch',
                ],
                [
                    'key' => 'cartao_imagem_verso',
                    'label' => 'Imagem de Verso',
                    'description' => 'Fundo do verso do cartão dinâmico.',
                    'type' => 'image',
                    'category' => 'Cartão Dinâmico',
                    'value' => $this->cartaoImagemUrl($tenantId, 'verso', $tenantDetail->cartao_imagem_verso),
                    'updated_at' => $atualizadoEm,
                    'upload_route' => 'configuracao.cartao-dinamico.imagem.store',
                    'upload_route_params' => ['verso'],
                    'delete_route' => 'configuracao.cartao-dinamico.imagem.destroy',
                    'delete_route_params' => ['verso'],
                    'upload_mode' => 'fetch',
                ],
                [
                    'key' => 'cartao_dinamico_status',
                    'label' => 'Cartão Dinâmico Ativo',
                    'description' => 'Quando ativado, o botão "Cartão Dinâmico" fica disponível na listagem de pacientes para gerar o cartão com identidade visual própria.',
                    'type' => 'toggle',
                    'category' => 'Cartão Dinâmico',
                    'value' => $cartaoDinamicoEnabled,
                    'toggle_route' => 'configuracao.cartao-dinamico.toggle',
                ],
                [
                    'key' => 'cartao_qrcode',
                    'label' => 'QR Code e Textos do Verso',
                    'description' => 'Habilite o QR Code exibido no verso do cartão, informe os dados que serão codificados (URL, texto, etc.) e edite os textos fixos do verso.',
                    'type' => 'qrcode',
                    'category' => 'Cartão Dinâmico',
                    'value' => [
                        'habilitado' => (bool) ($tenantDetail->cartao_qrcode_habilitado ?? false),
                        'dados' => $tenantDetail->cartao_qrcode_dados ?? '',
                        'texto_info' => $tenantDetail->cartao_verso_texto_info ?: 'Solicite atendimento 24h',
                        'rodape' => $tenantDetail->cartao_verso_rodape ?: "Apresente este cartão nos locais conveniados\no obtenha benefícios especiais\nConsute o regulamento em nosso site:\nwww.integralmedben.com.br",
                    ],
                    'save_route' => 'configuracao.cartao-dinamico.qrcode',
                ],
            );
        }

        return Inertia::render('Tenant/Configuracao/Index', [
            'configurations' => $configurations,
        ]);
    }

    private function cartaoImagemUrl(string $tenantId, string $tipo, ?string $fileName): ?string
    {
        if (! $fileName) {
            return null;
        }

        return route('pagina.configuracao.cartao-dinamico.imagem.show', [$tenantId, $tipo]).'?v='.urlencode($fileName);
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
            $fileName = 'logo_'.$tenantDetail->id.'_'.time().'.'.$file->getClientOriginalExtension();

            $this->ensureDiskRootExists('tenants');

            $file->storeAs('', $fileName, 'tenants');

            if ($tenantDetail->logo) {
                foreach (tenant()->logoPathCandidates($tenantDetail->logo) as $oldPath) {
                    if (Storage::disk('tenants')->exists($oldPath)) {
                        Storage::disk('tenants')->delete($oldPath);
                    }
                }
            }

            $tenantDetail->update([
                'logo' => $fileName,
            ]);
        }

        return redirect()->back()->with('success', 'Logo atualizado com sucesso!');
    }

    private function ensureDiskRootExists(string $disk): void
    {
        $root = Storage::disk($disk)->path('');

        if (! is_dir($root)) {
            @mkdir($root, 0755, true);
        }

        @chmod($root, 0755);
    }

    public function updateCartaoDinamicoCores(Request $request)
    {
        $request->validate([
            'cartao_cor_primaria' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'cartao_cor_secundaria' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'cartao_cor_texto' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'cartao_fonte' => ['nullable', 'string', 'in:'.implode(',', PaginaCartaoDinamicoController::FONTES_DISPONIVEIS)],
        ]);

        $tenantDetail = TenantsDetail::firstOrCreate(['tenant_id' => $this->tenantId()]);

        $tenantDetail->update([
            'cartao_cor_primaria' => $request->input('cartao_cor_primaria'),
            'cartao_cor_secundaria' => $request->input('cartao_cor_secundaria'),
            'cartao_cor_texto' => $request->input('cartao_cor_texto'),
            'cartao_fonte' => $request->input('cartao_fonte'),
        ]);

        return redirect()->back()->with('success', 'Estilo do Cartão Dinâmico atualizado com sucesso!');
    }

    public function updateCartaoQrCode(Request $request)
    {
        $request->validate([
            'cartao_qrcode_habilitado' => ['required', 'boolean'],
            'cartao_qrcode_dados' => ['nullable', 'string', 'max:500'],
            'cartao_verso_texto_info' => ['nullable', 'string', 'max:255'],
            'cartao_verso_rodape' => ['nullable', 'string', 'max:1000'],
        ]);

        $tenantDetail = TenantsDetail::firstOrCreate(['tenant_id' => $this->tenantId()]);

        $tenantDetail->update([
            'cartao_qrcode_habilitado' => $request->boolean('cartao_qrcode_habilitado'),
            'cartao_qrcode_dados' => $request->input('cartao_qrcode_dados'),
            'cartao_verso_texto_info' => $request->input('cartao_verso_texto_info'),
            'cartao_verso_rodape' => $request->input('cartao_verso_rodape'),
        ]);

        return redirect()->back()->with('success', 'Configuração do QR Code e textos do verso atualizada com sucesso!');
    }

    public function storeCartaoDinamicoImagem(Request $request, string $tipo)
    {
        if (! isset(self::CARTAO_TIPO_COLUNA[$tipo])) {
            abort(404);
        }

        $request->validate([
            'imagem' => ['required', 'image', 'max:2048'],
        ]);

        $coluna = self::CARTAO_TIPO_COLUNA[$tipo];
        $tenantId = $this->tenantId();
        $detail = TenantsDetail::firstOrCreate(['tenant_id' => $tenantId]);

        $file = $request->file('imagem');
        $fileName = 'cartao_'.$tipo.'_'.$detail->id.'_'.time().'.'.$file->getClientOriginalExtension();

        $this->ensureDiskRootExists('tenants');

        $file->storeAs('', $fileName, 'tenants');

        if ($detail->{$coluna}) {
            $this->deleteCartaoAssetEverywhere($detail->{$coluna});
        }

        $detail->update([$coluna => $fileName]);

        return response()->json([
            'success' => true,
            'imagem' => [
                'tipo' => $tipo,
                'url' => $this->cartaoImagemUrl($tenantId, $tipo, $fileName),
                'nome' => $fileName,
            ],
        ]);
    }

    public function destroyCartaoDinamicoImagem(string $tipo)
    {
        if (! isset(self::CARTAO_TIPO_COLUNA[$tipo])) {
            abort(404);
        }

        $coluna = self::CARTAO_TIPO_COLUNA[$tipo];
        $detail = TenantsDetail::where('tenant_id', $this->tenantId())->first();

        if (! $detail || ! $detail->{$coluna}) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma imagem encontrada para este tipo.',
            ], 404);
        }

        $this->deleteCartaoAssetEverywhere($detail->{$coluna});

        $detail->update([$coluna => null]);

        return response()->json(['success' => true]);
    }

    public function toggleCartaoDinamico()
    {
        try {
            $detail = TenantsDetail::firstOrCreate(['tenant_id' => $this->tenantId()]);

            $config = $detail->configuracao ?? [];
            $current = $config['cartao_dinamico_enabled'] ?? false;
            $config['cartao_dinamico_enabled'] = ! $current;
            $detail->update(['configuracao' => $config]);

            return redirect()->back()->with('success', 'Cartão Dinâmico atualizado com sucesso.');
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar cartao_dinamico_enabled (tenant)', [
                'tenant_id' => $this->tenantId(),
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('message', 'Não foi possível atualizar o status.')
                ->with('type', 'error');
        }
    }

    private function deleteCartaoAssetEverywhere(string $fileName): void
    {
        foreach (tenant()->cartaoAssetPathCandidates($fileName) as $path) {
            if (Storage::disk('tenants')->exists($path)) {
                Storage::disk('tenants')->delete($path);
            }
        }
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

    public function toggleCartaoPaciente(Tenant $tenant)
    {
        try {
            $detail = TenantsDetail::firstOrCreate([
                'tenant_id' => $tenant->id,
            ]);

            $config = $detail->configuracao ?? [];
            $current = $config['cartao_paciente_enabled'] ?? false;
            $config['cartao_paciente_enabled'] = ! $current;
            $detail->update(['configuracao' => $config]);

            return redirect()
                ->route('pagina.show', $tenant->id)
                ->with('message', 'Cartão de Paciente atualizado com sucesso.')
                ->with('type', 'success');
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar cartao_paciente_enabled', [
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
                $cpfQuestion = Question::where('role', QuestionRoleEnum::Cpf)->first();
                $nomeQuestion = Question::where('role', QuestionRoleEnum::Nome)->first();

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

                    $cpf = preg_replace('/\D/', '', $item['cpfCnpj'] ?? '');

                    if ($cpf && $cpfQuestion && $nomeQuestion) {
                        $existing = CentralPatientAnswer::where('question_id', $cpfQuestion->id)
                            ->where('answer', $cpf)
                            ->whereHas('patient', function ($q) use ($tenant) {
                                $q->where('tenant_id', $tenant->id);
                            })
                            ->exists();

                        if (!$existing) {
                            $centralPatient = CentralPatient::create([
                                'tenant_id' => $tenant->id,
                            ]);

                            CentralPatientAnswer::create([
                                'central_patient_id' => $centralPatient->id,
                                'question_id' => $nomeQuestion->id,
                                'answer' => $item['nomePessoa'] ?? '',
                            ]);

                            CentralPatientAnswer::create([
                                'central_patient_id' => $centralPatient->id,
                                'question_id' => $cpfQuestion->id,
                                'answer' => $cpf,
                            ]);

                            $centralPatientId = $centralPatient->id;

                            Log::info('Telemedicina | Paciente criado via SIPROV (central)', [
                                'tenant_id' => $tenant->id,
                                'cpf' => $cpf,
                                'nome' => $item['nomePessoa'] ?? '',
                            ]);
                        } else {
                            $centralAnswer = CentralPatientAnswer::where('question_id', $cpfQuestion->id)
                                ->where('answer', $cpf)
                                ->whereHas('patient', function ($q) use ($tenant) {
                                    $q->where('tenant_id', $tenant->id);
                                })
                                ->first();

                            $centralPatientId = $centralAnswer?->central_patient_id;
                        }

                        if ($centralPatientId) {
                            $hasPatient = $tenant->run(function () use ($cpf) {
                                return \App\Models\Patient::where('cpf', $cpf)->exists();
                            });

                            if (!$hasPatient) {
                                $tenant->run(function () use ($centralPatientId, $item, $cpf) {
                                    $sexo = match (strtoupper($item['sexo'] ?? '')) {
                                        'M', 'MASCULINO' => 'masculino',
                                        'F', 'FEMININO' => 'feminino',
                                        default => null,
                                    };

                                    $dataNascimento = null;
                                    if (!empty($item['dataNascimento'])) {
                                        try {
                                            $dataNascimento = \Carbon\Carbon::createFromFormat('d/m/Y', $item['dataNascimento']);
                                        } catch (\Exception $e) {
                                            $dataNascimento = null;
                                        }
                                    }

                                    \App\Models\Patient::create([
                                        'central_patient_id' => $centralPatientId,
                                        'nome' => $item['nomePessoa'] ?? '',
                                        'cpf' => $cpf,
                                        'email' => $item['email'] ?? null,
                                        'numero' => preg_replace('/\D/', '', $item['telefoneCelular'] ?? '') ?: null,
                                        'sexo' => $sexo,
                                        'data_nascimento' => $dataNascimento,
                                        'status' => true,
                                        'status_registro' => 'vinculo',
                                    ]);
                                });

                                Log::info('Telemedicina | Paciente criado via SIPROV (tenant)', [
                                    'tenant_id' => $tenant->id,
                                    'cpf' => $cpf,
                                    'nome' => $item['nomePessoa'] ?? '',
                                ]);
                            }
                        }
                    }
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
            $cpf = preg_replace('/\D/', '', $telemedicinaTenant->data['cpf_cnpj'] ?? '');

            if ($cpf) {
                $tenant->run(function () use ($cpf) {
                    \App\Models\Patient::where('cpf', $cpf)
                        ->where('status_registro', 'vinculo')
                        ->delete();
                });

                $cpfQuestion = Question::where('role', QuestionRoleEnum::Cpf)->first();
                if ($cpfQuestion) {
                    CentralPatientAnswer::where('question_id', $cpfQuestion->id)
                        ->where('answer', $cpf)
                        ->delete();
                }
            }

            $telemedicinaTenant->delete();

            return redirect()
                ->route('pagina.show', $tenant->id)
                ->with('message', 'Vínculo e paciente removidos com sucesso.')
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
