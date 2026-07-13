<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\CredenciasCluble;
use App\Models\Form;
use App\Models\FormArquivo;
use App\Models\FormResponse;
use App\Models\Patient;
use App\Models\PatientAnswer;
use App\Models\Question;
use App\Models\TenantsDetail;
use App\Models\Tenant;
use App\Services\ClubleBeneficiarioService;
use App\Services\SimpleSmsService;
use App\Services\Tenant\FormsResponseTenentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Stancl\Tenancy\TenantCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class PublicFormController extends Controller
{
    public function __construct(
        protected ClubleBeneficiarioService $beneficiarioService,
        private SimpleSmsService $simpleSmsService,
        private FormsResponseTenentService $formsResponseTenentService

    ) {}

    private function canAcceptResponses(Form $form): bool
    {
        return $form->status === 'ativo';
    }

    private function getLogoData(Form $form): ?array
    {
        $logoArquivo = $form->arquivos->first();
        if (! $logoArquivo) {
            return null;
        }

        return [
            'url' => $logoArquivo->url,
            'posicao' => $logoArquivo->pivot->posicao ?? 'centro',
        ];
    }

    private function convertBrazilianDateToISO(string $date): string
    {
        $parts = explode('/', $date);
        if (count($parts) === 3) {
            return "{$parts[2]}-{$parts[1]}-{$parts[0]}";
        }

        return $date;
    }

    private function isDateField($field): bool
    {
        $label = strtolower($field->label);

        return $field->type === 'date' ||
        str_contains($label, 'nascimento') ||
        str_contains($label, 'data') ||
        str_contains($label, 'birth');
    }

    private function isCPFField($field): bool
    {
        $label = strtolower($field->label);

        return $field->type === 'cpf' ||
        str_contains($label, 'cpf') ||
        str_contains($label, 'c.p.f');
    }

    public function show(string $slug): Response
    {
        try {
            $form = Form::where('slug', $slug)
                ->where('is_public', true)
                ->with(['fields' => fn ($q) => $q->orderBy('order')])
                ->with(['arquivos' => fn ($q) => $q
                    ->wherePivot('tipo', FormArquivo::TIPO_LOGO)
                    ->withPivot('posicao', 'tipo'),
                ])
                ->first();
            if (! $form) {
                Log::warning('Formulário não encontrado', [
                    'slug' => $slug,
                    'ip' => request()->ip(),
                ]);
                abort(404, 'Formulário não encontrado.');
            }
            $logoData = $this->getLogoData($form);
            if (! $this->canAcceptResponses($form)) {
                $statusLabels = [
                    'rascunho' => 'em edição (rascunho)',
                    'pausado' => 'pausado',
                    'encerrado' => 'encerrado',
                ];
                $label = $statusLabels[$form->status] ?? $form->status;
                Log::info('Tentativa de acesso a formulário não ativo', [
                    'form_id' => $form->id,
                    'slug' => $slug,
                    'status' => $form->status,
                    'ip' => request()->ip(),
                ]);

                return Inertia::render('Form/Public/Show', [
                    'form' => [
                        'title' => $form->title,
                        'status' => $form->status,
                        'statusLabel' => $label,
                        'primary_color' => $form->primary_color,
                        'secondary_color' => $form->secondary_color,
                        'lei' => $form->lei,
                        'logo' => $logoData,
                        'btn_confirmar_descricao' => $form->btn_confirmar_descricao ?? null,
                        'sub_descricao' => $form->sub_descricao ?? null,
                        'observacao' => $form->observacao ?? null,
                        'credencia_cluble_id' => $form->credencia_cluble_id ?? null,
                        'message' => "Este formulário está {$label} e não pode receber respostas no momento.",
                        'instruction' => 'Para permitir o preenchimento, altere o status para "Ativo" nas configurações.',
                        'canActivate' => true,
                    ],
                ]);
            }
            if ($form->expires_at && $form->expires_at <= now()) {
                Log::info('Tentativa de acesso a formulário expirado', [
                    'form_id' => $form->id,
                    'slug' => $slug,
                    'expires_at' => $form->expires_at,
                ]);

                return Inertia::render('Form/Public/Show', [
                    'form' => [
                        'title' => $form->title,
                        'slug' => $slug,
                        'status' => 'expirado',
                        'statusLabel' => 'expirado',
                        'primary_color' => $form->primary_color,
                        'secondary_color' => $form->secondary_color,
                        'lei' => $form->lei,
                        'logo' => $logoData,
                        'btn_confirmar_descricao' => $form->btn_confirmar_descricao ?? null,
                        'sub_descricao' => $form->sub_descricao ?? null,
                        'observacao' => $form->observacao ?? null,
                        'credencia_cluble_id' => $form->credencia_cluble_id ?? null,
                        'message' => 'Este formulário expirou e não está mais disponível para respostas.',
                        'instruction' => 'Renove a data de expiração nas configurações para reativá-lo.',
                        'canActivate' => false,
                    ],
                ]);
            }
            if ($form->response_limit && $form->responses_count >= $form->response_limit) {
                Log::info('Formulário atingiu limite de respostas', [
                    'form_id' => $form->id,
                    'slug' => $slug,
                    'response_limit' => $form->response_limit,
                    'responses_count' => $form->responses_count,
                ]);

                return Inertia::render('Form/Public/Show', [
                    'form' => [
                        'title' => $form->title,
                        'primary_color' => $form->primary_color,
                        'secondary_color' => $form->secondary_color,
                        'lei' => $form->lei,
                        'btn_confirmar_descricao' => $form->btn_confirmar_descricao ?? null,
                        'sub_descricao' => $form->sub_descricao ?? null,
                        'observacao' => $form->observacao ?? null,
                        'credencia_cluble_id' => $form->credencia_cluble_id ?? null,
                        'status' => 'limite_atingido',
                        'statusLabel' => 'com limite atingido',
                        'message' => 'Este formulário atingiu o limite máximo de respostas.',
                        'instruction' => 'Aumente o limite de respostas nas configurações ou crie um novo formulário.',
                        'canActivate' => false,
                        'logo' => $logoData,
                    ],
                ]);
            }

            return Inertia::render('Form/Public/Show', [
                'form' => [
                    'id' => $form->id,
                    'title' => $form->title,
                    'slug' => $slug,
                    'description' => $form->description,
                    'expires_at' => $form->expires_at,
                    'response_limit' => $form->response_limit,
                    'responses_count' => $form->responses_count,
                    'is_public' => $form->is_public,
                    'primary_color' => $form->primary_color,
                    'secondary_color' => $form->secondary_color,
                    'lei' => $form->lei,
                    'status' => $form->status,
                    'logo' => $logoData,
                    'credencia_cluble_id' => $form->credencia_cluble_id ?? null,
                    'btn_confirmar_descricao' => $form->btn_confirmar_descricao ?? null,
                    'sub_descricao' => $form->sub_descricao ?? null,
                    'observacao' => $form->observacao ?? null,
                    'fields' => $form->fields->map(fn ($f) => [
                        'id' => $f->id,
                        'type' => $f->type,
                        'label' => $f->label,
                        'placeholder' => $f->placeholder,
                        'required' => $f->required,
                        'options' => $f->options,
                        'help_text' => $f->help_text,
                        'is_date' => $this->isDateField($f),
                        'is_cpf' => $this->isCPFField($f),
                    ]),
                ],
            ]);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Erro ao exibir formulário', [
                'slug' => $slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'Erro ao carregar o formulário. Tente novamente mais tarde.');
        }
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $form = Form::where('slug', $slug)
                ->where('is_public', true)
                ->with('fields')
                ->lockForUpdate()
                ->first();
            if (! $form) {
                abort(404, 'Formulário não encontrado.');
            }
            if (! $this->canAcceptResponses($form)) {
                DB::rollBack();
                Log::warning('Tentativa de envio para formulário não ativo', [
                    'form_id' => $form->id,
                    'slug' => $slug,
                    'status' => $form->status,
                    'ip' => $request->ip(),
                ]);

                return redirect()
                    ->back()
                    ->with('error', "Este formulário está {$form->status} e não pode receber respostas. Entre em contato com o administrador.");
            }
            if ($form->expires_at && $form->expires_at <= now()) {
                DB::rollBack();
                abort(403, 'Este formulário expirou.');
            }
            if ($form->response_limit && $form->responses_count >= $form->response_limit) {
                DB::rollBack();
                abort(403, 'Limite de respostas atingido.');
            }
            $rules = [];
            $messages = [];
            foreach ($form->fields as $field) {
                $fieldRules = [];
                if ($field->required) {
                    $fieldRules[] = 'required';
                    $messages["answers.{$field->id}.required"] = "O campo \"{$field->label}\" é obrigatório.";
                } else {
                    $fieldRules[] = 'nullable';
                }
                switch ($field->type) {
                    case 'email':
                        $fieldRules[] = 'email:rfc,dns';
                        break;
                    case 'number':
                        $fieldRules[] = 'numeric';
                        break;
                    case 'date':
                        $fieldRules[] = 'date_format:d/m/Y';
                        $messages["answers.{$field->id}.date_format"] = "O campo \"{$field->label}\" deve estar no formato DD/MM/AAAA.";
                        break;
                    case 'cpf':
                        $fieldRules[] = 'numeric';
                        $fieldRules[] = 'digits:11';
                        $messages["answers.{$field->id}.numeric"] = "O campo \"{$field->label}\" deve conter apenas números.";
                        $messages["answers.{$field->id}.digits"] = "O campo \"{$field->label}\" deve ter exatamente 11 dígitos.";
                        break;
                }
                $rules["answers.{$field->id}"] = $fieldRules;
            }
            $validated = $request->validate($rules, $messages);
            $processedAnswers = [];
            foreach ($form->fields as $field) {
                $answer = $validated['answers'][$field->id] ?? null;
                if ($answer && $this->isDateField($field)) {
                    $processedAnswers[$field->id] = $this->convertBrazilianDateToISO($answer);
                } else {
                    $processedAnswers[$field->id] = $answer;
                }
            }
            $formResponse = FormResponse::create([
                'form_id' => $form->id,
                'user_id' => auth()->id(),
                'answers' => $processedAnswers,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            $form->increment('responses_count');
            if ($form->credencia_cluble_id) {
                $this->sincronizarComClube($form, $formResponse, $validated['answers']);
            }
            $host = request()->getHost();
            $currentTenant = str($host)->before('.')->toString();
            if ($currentTenant != null) {
                $this->formsResponseTenentService->create($currentTenant, $form->id, $formResponse->id);
            }

            DB::commit();

            if ($currentTenant != null) {
                $this->criarPacienteDinamico($currentTenant, $form, $formResponse, $processedAnswers);
            }

            return redirect()
                ->route('forms.public.thanks', $slug)
                ->with('success', 'Resposta enviada com sucesso!');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao salvar resposta', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erro ao enviar resposta. Tente novamente.');
        }
    }

    public function thanks(string $slug): Response
    {
        $form = Form::where('slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        return Inertia::render('Form/Public/Thanks', [
            'form' => [
                'title' => $form->title,
                'description' => $form->description,
                'slug' => $form->slug,
                'primary_color' => $form->primary_color,
                'secondary_color' => $form->secondary_color,
                'lei' => $form->lei,
            ],
        ]);
    }

    private function sincronizarComClube(Form $form, FormResponse $formResponse, array $answers): void
    {
        $credencial = CredenciasCluble::find($form->credencia_cluble_id);
        if (! $credencial || $credencial->isTokenExpired()) {
            Log::warning('Credencial inválida ou expirada', [
                'form_id' => $form->id,
                'credencial_id' => $form->credencia_cluble_id,
            ]);

            return;
        }
        $fields = $form->fields()->orderBy('order')->get();
        $dados = $this->mapearRespostasPorOrdem($fields, $answers);
        if (! empty($dados['cellphone'])) {
            $empresa = $form->title ?? 'Empresa';
            $cliente = $dados['name'] ?? 'Cliente';
            $data = now()->format('d/m/Y');
            $hora = now()->format('H:i');
            $conteudo = "$empresa, Olá $cliente! Seu cadastro foi confirmado no dia $data às $hora.";
            $resultado = $this->simpleSmsService->send(
                $dados['cellphone'],
                $conteudo,
            );
        }
        Log::info('Dados mapeados para API Clube', [
            'form_id' => $form->id,
            'dados' => $dados,
        ]);
        if (empty($dados['name']) || empty($dados['email']) || empty($dados['cpf'])) {
            Log::warning('Dados obrigatórios faltando', [
                'form_id' => $form->id,
                'dados' => $dados,
                'tem_name' => ! empty($dados['name']),
                'tem_email' => ! empty($dados['email']),
                'tem_cpf' => ! empty($dados['cpf']),
            ]);

            return;
        }
        $resultado = $this->beneficiarioService->cadastrarBeneficiario($dados, $credencial);
        if ($resultado && $resultado['success']) {
            $formResponse->update([
                'external_beneficiario_id' => $resultado['data']['id'] ?? null,
                'sincronizado_clube' => true,
                'sincronizado_em' => now(),
            ]);
        } else {
            $formResponse->update([
                'erro_sincronizacao' => $resultado['error']['message'] ?? 'Erro desconhecido',
                'tentativas_sincronizacao' => ($formResponse->tentativas_sincronizacao ?? 0) + 1,
            ]);
            Log::error('Falha sincronização Clube', [
                'form_id' => $form->id,
                'response_id' => $formResponse->id,
                'error' => $resultado['error'] ?? 'Sem resposta',
            ]);
        }
    }

    private function mapearRespostasPorOrdem($fields, array $answers): array
    {
        $respostas = array_values($answers);
        $dados = [
            'name' => null,
            'email' => null,
            'cpf' => null,
            'birth_date' => null,
            'cellphone' => null,
            'company_name' => null,
            'expiration_date' => null,
            'password' => null,
            'newsletter' => false,
            'sms' => false,
            'whatsapp' => false,
            'authorized' => true,
        ];
        foreach ($fields as $index => $field) {
            $valor = $respostas[$index] ?? null;
            if (empty($valor)) {
                continue;
            }
            $campoClube = $this->identificarCampoClube($field);
            if (! $campoClube) {
                continue;
            }
            $dados[$campoClube] = $this->formatarValor($valor, $campoClube, $field->type);
        }

        return array_filter($dados, function ($v, $k) {
            if (in_array($k, ['newsletter', 'sms', 'whatsapp', 'authorized'])) {
                return true;
            }

            return $v !== null && $v !== '';
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function identificarCampoClube($field): ?string
    {
        $label = mb_strtolower(trim($field->label));
        $type = $field->type;

        return match (true) {
            str_contains($label, 'nome') && str_contains($label, 'completo') => 'name',
            str_contains($label, 'e-mail') || str_contains($label, 'email') || $type === 'email' => 'email',
            str_contains($label, 'cpf') => 'cpf',
            str_contains($label, 'nascimento') ||
            (str_contains($label, 'data') && str_contains($label, 'nasc')) ||
            ($type === 'date' && str_contains($label, 'nasc')) => 'birth_date',
            str_contains($label, 'celular') ||
            str_contains($label, 'telefone') ||
            str_contains($label, 'fone') ||
            str_contains($label, 'tel') ||
            str_contains($label, 'whatsapp') => 'cellphone',
            str_contains($label, 'empresa') ||
            str_contains($label, 'company') ||
            str_contains($label, 'organização') => 'company_name',
            str_contains($label, 'expiração') ||
            str_contains($label, 'validade') ||
            str_contains($label, 'vencimento') => 'expiration_date',
            str_contains($label, 'senha') ||
            str_contains($label, 'password') => 'password',
            default => null,
        };
    }

    private function formatarValor(mixed $valor, string $campoClube, string $fieldType): mixed
    {
        $valor = is_string($valor) ? trim($valor) : $valor;

        return match ($campoClube) {
            'name' => mb_substr($valor, 0, 100),
            'email' => mb_strtolower(mb_substr($valor, 0, 80)),
            'cpf' => mb_substr(preg_replace('/[^0-9]/', '', $valor), 0, 11),
            'birth_date' => $this->formatarData($valor),
            'cellphone' => $this->formatarTelefone($valor),
            'company_name' => mb_substr($valor, 0, 255) ?: null,
            'expiration_date' => $this->formatarData($valor),
            'password' => $valor,
            'newsletter', 'sms', 'whatsapp', 'authorized' => (bool) $valor,
            default => $valor,
        };
    }

    private function formatarData(?string $data): ?string
    {
        if (! $data) {
            return null;
        }
        $data = trim($data);
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data)) {
            return $data;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return Carbon::createFromFormat('Y-m-d', $data)->format('d/m/Y');
        }
        try {
            return Carbon::parse($data)->format('d/m/Y');
        } catch (\Exception $e) {
            Log::warning('Não foi possível formatar data', ['data' => $data]);

            return $data;
        }
    }

    private function formatarTelefone(?string $tel): ?string
    {
        if (! $tel) {
            return null;
        }
        $n = preg_replace('/[^0-9]/', '', $tel);
        if (strlen($n) === 11) {
            return '('.substr($n, 0, 2).') '.substr($n, 2, 5).'-'.substr($n, 7);
        }
        if (strlen($n) === 10) {
            return '('.substr($n, 0, 2).') '.substr($n, 2, 4).'-'.substr($n, 6);
        }

        return mb_substr($tel, 0, 15);
    }

    private function criarPacienteDinamico(
        string $tenantId,
        Form $form,
        FormResponse $formResponse,
        array $processedAnswers,
    ): void {
        try {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                return;
            }

            $detail = TenantsDetail::where('tenant_id', $tenantId)->first();
            $config = $detail->configuracao ?? [];
            if (empty($config['status_formulario_dinamico'])) {
                return;
            }

            $fields = $form->fields()->orderBy('order')->get();
            $fieldAnswers = [];
            foreach ($fields as $field) {
                $fieldAnswers[$field->id] = $processedAnswers[$field->id] ?? null;
            }

            $questions = Question::all();

            $patientId = null;
            $answersPorQuestion = [];

            $tenant->run(function () use ($tenantId, $fieldAnswers, $fields, $questions, &$patientId, &$answersPorQuestion) {
                $patientData = $this->extrairDadosPaciente($fieldAnswers, $fields, $questions);
                $patient = Patient::create([
                    'status_registro' => \App\Enums\StatusRegistroEnum::FormDinamico,
                    'nome' => $patientData['nome'],
                    'cpf' => $patientData['cpf'],
                    'email' => $patientData['email'],
                    'data_nascimento' => $patientData['data_nascimento'],
                    'numero' => $patientData['numero'],
                    'sexo' => $patientData['sexo'],
                ]);

                $answersPorQuestion = $this->mapearRespostasParaQuestions($fieldAnswers, $fields, $questions);
                foreach ($answersPorQuestion as $questionId => $answer) {
                    if ($answer === null || $answer === '') {
                        continue;
                    }
                    PatientAnswer::create([
                        'patient_id' => $patient->id,
                        'question_id' => $questionId,
                        'answer' => (string) $answer,
                    ]);
                }

                $patientId = $patient->id;

                Log::info('Paciente criado dinamicamente via formulário público', [
                    'patient_id' => $patient->id,
                    'tenant_id' => $tenantId,
                    'form_id' => $form->id,
                    'response_id' => $formResponse->id,
                ]);
            });

            if ($patientId) {
                \App\Events\PatientCreated::dispatch($patientId, $tenantId, $answersPorQuestion);
            }
        } catch (Throwable $e) {
            Log::error('Erro ao criar paciente dinamicamente', [
                'tenant_id' => $tenantId,
                'form_id' => $form->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function extrairDadosPaciente(array $fieldAnswers, $fields, $questions): array
    {
        $dados = [
            'nome' => null,
            'cpf' => null,
            'email' => null,
            'data_nascimento' => null,
            'numero' => null,
            'sexo' => null,
        ];

        foreach ($fields as $field) {
            $valor = $fieldAnswers[$field->id] ?? null;
            if (empty($valor)) {
                continue;
            }
            $label = mb_strtolower(trim($field->label));

            if (empty($dados['nome']) && str_contains($label, 'nome')) {
                $dados['nome'] = (string) $valor;
            } elseif (empty($dados['cpf']) && str_contains($label, 'cpf')) {
                $dados['cpf'] = preg_replace('/\D/', '', (string) $valor);
            } elseif (empty($dados['email']) && (str_contains($label, 'e-mail') || str_contains($label, 'email') || $field->type === 'email')) {
                $dados['email'] = mb_strtolower(trim((string) $valor));
            } elseif (empty($dados['data_nascimento']) && (str_contains($label, 'nascimento') || ($field->type === 'date' && str_contains($label, 'nasc')))) {
                $dados['data_nascimento'] = $this->convertBrazilianDateToISO((string) $valor);
            } elseif (empty($dados['numero']) && (str_contains($label, 'celular') || str_contains($label, 'telefone') || str_contains($label, 'tel') || str_contains($label, 'whatsapp'))) {
                $dados['numero'] = (string) $valor;
            } elseif (empty($dados['sexo']) && str_contains($label, 'sexo')) {
                $dados['sexo'] = $this->normalizarSexo((string) $valor);
            }
        }

        return $dados;
    }

    private function normalizarSexo(string $valor): ?string
    {
        $valor = mb_strtolower(trim($valor));

        return match (true) {
            in_array($valor, ['m', 'masculino', 'homme']) => 'M',
            in_array($valor, ['f', 'feminino', 'femme']) => 'F',
            default => null,
        };
    }

    private function mapearRespostasParaQuestions(array $fieldAnswers, $fields, $questions): array
    {
        $result = [];
        foreach ($fields as $field) {
            $valor = $fieldAnswers[$field->id] ?? null;
            if ($valor === null || $valor === '') {
                continue;
            }
            $label = mb_strtolower(trim($field->label));
            $question = $questions->first(function ($q) use ($label, $field) {
                $qTitle = mb_strtolower(trim($q->title));
                if ($qTitle === $label) {
                    return true;
                }
                if (str_contains($qTitle, $label) || str_contains($label, $qTitle)) {
                    return true;
                }
                if ($q->type === $field->type) {
                    $qWords = explode(' ', $qTitle);
                    $fWords = explode(' ', $label);
                    $intersection = array_intersect($qWords, $fWords);
                    if (count($intersection) >= 1) {
                        return true;
                    }
                }

                return false;
            });
            if ($question) {
                $result[$question->id] = (string) $valor;
            }
        }

        return $result;
    }
}
