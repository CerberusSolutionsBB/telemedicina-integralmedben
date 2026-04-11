<?php
namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\CredenciasCluble;
use App\Models\Form;
use App\Models\FormArquivo;
use App\Models\FormResponse;
use App\Services\ClubleBeneficiarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class PublicFormController extends Controller
{
    public function __construct(
        protected ClubleBeneficiarioService $beneficiarioService
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
            'url'     => $logoArquivo->url,
            'posicao' => $logoArquivo->pivot->posicao ?? 'centro',
        ];
    }

    // ⭐ Converte data brasileira (DD/MM/YYYY) para formato ISO (YYYY-MM-DD) para salvar
    private function convertBrazilianDateToISO(string $date): string
    {
        $parts = explode('/', $date);
        if (count($parts) === 3) {
            return "{$parts[2]}-{$parts[1]}-{$parts[0]}";
        }
        return $date;
    }

    // ⭐ Verifica se é campo de data
    private function isDateField($field): bool
    {
        $label = strtolower($field->label);
        return $field->type === 'date' ||
        str_contains($label, 'nascimento') ||
        str_contains($label, 'data') ||
        str_contains($label, 'birth');
    }

    // ⭐ Verifica se é campo de CPF
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
                ->with(['fields' => fn($q) => $q->orderBy('order')])
                ->with(['arquivos' => fn($q) => $q
                        ->wherePivot('tipo', FormArquivo::TIPO_LOGO)
                        ->withPivot('posicao', 'tipo'),
                ])
                ->first();
            if (! $form) {
                Log::warning('Formulário não encontrado', [
                    'slug' => $slug,
                    'ip'   => request()->ip(),
                ]);
                abort(404, 'Formulário não encontrado.');
            }
            $logoData = $this->getLogoData($form);
            if (! $this->canAcceptResponses($form)) {
                $statusLabels = [
                    'rascunho'  => 'em edição (rascunho)',
                    'pausado'   => 'pausado',
                    'encerrado' => 'encerrado',
                ];
                $label = $statusLabels[$form->status] ?? $form->status;
                Log::info('Tentativa de acesso a formulário não ativo', [
                    'form_id' => $form->id,
                    'slug'    => $slug,
                    'status'  => $form->status,
                    'ip'      => request()->ip(),
                ]);
                return Inertia::render('Form/Public/Show', [
                    'form'        => [
                        'title'                   => $form->title,
                        'status'                  => $form->status,
                        'statusLabel'             => $label,
                        'primary_color'           => $form->primary_color,
                        'secondary_color'         => $form->secondary_color,
                        'lei'                     => $form->lei,
                        'logo'                    => $logoData,
                        'btn_confirmar_descricao' => $form->btn_confirmar_descricao ?? null,
                        'sub_descricao'           => $form->sub_descricao ?? null,
                        'observacao'              => $form->observacao ?? null,
                        'credencia_cluble_id'     => $form->credencia_cluble_id ?? null,
                        'message'                 => "Este formulário está {$label} e não pode receber respostas no momento.",
                        'instruction' => 'Para permitir o preenchimento, altere o status para "Ativo" nas configurações.',
                        'canActivate' => true,
                    ],
                ]);
            }
            if ($form->expires_at && $form->expires_at <= now()) {
                Log::info('Tentativa de acesso a formulário expirado', [
                    'form_id'    => $form->id,
                    'slug'       => $slug,
                    'expires_at' => $form->expires_at,
                ]);
                return Inertia::render('Form/Public/Show', [
                    'form' => [
                        'title'                   => $form->title,
                        'slug'                    => $slug,
                        'status'                  => 'expirado',
                        'statusLabel'             => 'expirado',
                        'primary_color'           => $form->primary_color,
                        'secondary_color'         => $form->secondary_color,
                        'lei'                     => $form->lei,
                        'logo'                    => $logoData,
                        'btn_confirmar_descricao' => $form->btn_confirmar_descricao ?? null,
                        'sub_descricao'           => $form->sub_descricao ?? null,
                        'observacao'              => $form->observacao ?? null,
                        'credencia_cluble_id'     => $form->credencia_cluble_id ?? null,
                        'message'                 => 'Este formulário expirou e não está mais disponível para respostas.',
                        'instruction'             => 'Renove a data de expiração nas configurações para reativá-lo.',
                        'canActivate'             => false,
                    ],
                ]);
            }
            if ($form->response_limit && $form->responses_count >= $form->response_limit) {
                Log::info('Formulário atingiu limite de respostas', [
                    'form_id'         => $form->id,
                    'slug'            => $slug,
                    'response_limit'  => $form->response_limit,
                    'responses_count' => $form->responses_count,
                ]);
                return Inertia::render('Form/Public/Show', [
                    'form' => [
                        'title'                   => $form->title,
                        'primary_color'           => $form->primary_color,
                        'secondary_color'         => $form->secondary_color,
                        'lei'                     => $form->lei,
                        'btn_confirmar_descricao' => $form->btn_confirmar_descricao ?? null,
                        'sub_descricao'           => $form->sub_descricao ?? null,
                        'observacao'              => $form->observacao ?? null,
                        'credencia_cluble_id'     => $form->credencia_cluble_id ?? null,
                        'status'                  => 'limite_atingido',
                        'statusLabel'             => 'com limite atingido',
                        'message'                 => 'Este formulário atingiu o limite máximo de respostas.',
                        'instruction'             => 'Aumente o limite de respostas nas configurações ou crie um novo formulário.',
                        'canActivate'             => false,
                        'logo'                    => $logoData,
                    ],
                ]);
            }
            return Inertia::render('Form/Public/Show', [
                'form' => [
                    'id'                      => $form->id,
                    'title'                   => $form->title,
                    'slug'                    => $slug,
                    'description'             => $form->description,
                    'expires_at'              => $form->expires_at,
                    'response_limit'          => $form->response_limit,
                    'responses_count'         => $form->responses_count,
                    'is_public'               => $form->is_public,
                    'primary_color'           => $form->primary_color,
                    'secondary_color'         => $form->secondary_color,
                    'lei'                     => $form->lei,
                    'status'                  => $form->status,
                    'logo'                    => $logoData,
                    'credencia_cluble_id'     => $form->credencia_cluble_id ?? null,
                    'btn_confirmar_descricao' => $form->btn_confirmar_descricao ?? null,
                    'sub_descricao'           => $form->sub_descricao ?? null,
                    'observacao'              => $form->observacao ?? null,
                    'fields'                  => $form->fields->map(fn($f) => [
                        'id'          => $f->id,
                        'type'        => $f->type,
                        'label'       => $f->label,
                        'placeholder' => $f->placeholder,
                        'required'    => $f->required,
                        'options'     => $f->options,
                        'help_text'   => $f->help_text,
                        'is_date'     => $this->isDateField($f),
                        'is_cpf'      => $this->isCPFField($f),
                    ]),
                ],
            ]);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Erro ao exibir formulário', [
                'slug'  => $slug,
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
                    'slug'    => $slug,
                    'status'  => $form->status,
                    'ip'      => $request->ip(),
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

            $rules    = [];
            $messages = [];

            foreach ($form->fields as $field) {
                $fieldRules = [];
                if ($field->required) {
                    $fieldRules[]                              = 'required';
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
                        // ⭐ VALIDAÇÃO: Espera formato brasileiro DD/MM/YYYY
                        $fieldRules[]                                 = 'date_format:d/m/Y';
                        $messages["answers.{$field->id}.date_format"] = "O campo \"{$field->label}\" deve estar no formato DD/MM/AAAA.";
                        break;
                    case 'cpf':
                        // ⭐ VALIDAÇÃO NUMERIC PARA CPF (11 dígitos)
                        $fieldRules[]                             = 'numeric';
                        $fieldRules[]                             = 'digits:11';
                        $messages["answers.{$field->id}.numeric"] = "O campo \"{$field->label}\" deve conter apenas números.";
                        $messages["answers.{$field->id}.digits"]  = "O campo \"{$field->label}\" deve ter exatamente 11 dígitos.";
                        break;
                }

                $rules["answers.{$field->id}"] = $fieldRules;
            }

            $validated = $request->validate($rules, $messages);

            // ⭐ PROCESSA RESPOSTAS: Converte datas brasileiras para ISO antes de salvar
            $processedAnswers = [];
            foreach ($form->fields as $field) {
                $answer = $validated['answers'][$field->id] ?? null;

                if ($answer && $this->isDateField($field)) {
                    // Converte DD/MM/YYYY -> YYYY-MM-DD para salvar no banco
                    $processedAnswers[$field->id] = $this->convertBrazilianDateToISO($answer);
                } else {
                    // CPF e outros campos: mantém como está
                    $processedAnswers[$field->id] = $answer;
                }
            }

            $formResponse = FormResponse::create([
                'form_id'    => $form->id,
                'user_id'    => auth()->id(),
                'answers'    => $processedAnswers,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $form->increment('responses_count');
            if ($form->credencia_cluble_id) {
                $this->sincronizarComClube($form, $formResponse, $validated['answers']);
            }

            DB::commit();

            return redirect()
                ->route('forms.public.thanks', $slug)
                ->with('success', 'Resposta enviada com sucesso!');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao salvar resposta', [
                'slug'  => $slug,
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
                'title'           => $form->title,
                'description'     => $form->description,
                'slug'            => $form->slug,
                'primary_color'   => $form->primary_color,
                'secondary_color' => $form->secondary_color,
                'lei'             => $form->lei,
            ],
        ]);
    }
    private function sincronizarComClube(Form $form, FormResponse $formResponse, array $answers): void
    {
        $credencial = CredenciasCluble::find($form->credencia_cluble_id);

        if (! $credencial || $credencial->isTokenExpired()) {
            Log::warning('Credencial inválida ou expirada', [
                'form_id'       => $form->id,
                'credencial_id' => $form->credencia_cluble_id,
            ]);
            return;
        }

        // Busca campos do formulário em ordem
        $fields = $form->fields()->orderBy('order')->get();

        // Mapeia respostas
        $dados = $this->mapearRespostasPorOrdem($fields, $answers);

        Log::info('Dados mapeados para API Clube', [
            'form_id' => $form->id,
            'dados'   => $dados,
        ]);

        // Valida dados obrigatórios (name, email, cpf são required na API)
        if (empty($dados['name']) || empty($dados['email']) || empty($dados['cpf'])) {
            Log::warning('Dados obrigatórios faltando', [
                'form_id'   => $form->id,
                'dados'     => $dados,
                'tem_name'  => ! empty($dados['name']),
                'tem_email' => ! empty($dados['email']),
                'tem_cpf'   => ! empty($dados['cpf']),
            ]);
            return;
        }

        $resultado = $this->beneficiarioService->cadastrarBeneficiario($dados, $credencial);

        if ($resultado && $resultado['success']) {
            $formResponse->update([
                'external_beneficiario_id' => $resultado['data']['id'] ?? null,
                'sincronizado_clube'       => true,
                'sincronizado_em'          => now(),
            ]);
        } else {
            $formResponse->update([
                'erro_sincronizacao'       => $resultado['error']['message'] ?? 'Erro desconhecido',
                'tentativas_sincronizacao' => ($formResponse->tentativas_sincronizacao ?? 0) + 1,
            ]);

            Log::error('Falha sincronização Clube', [
                'form_id'     => $form->id,
                'response_id' => $formResponse->id,
                'error'       => $resultado['error'] ?? 'Sem resposta',
            ]);
        }
    }

    /**
     * Mapeia respostas pela ordem dos campos
     */
    private function mapearRespostasPorOrdem($fields, array $answers): array
    {
        // Respostas em array indexado (0, 1, 2, 3, 4...)
        $respostas = array_values($answers);

        // Inicializa com valores padrão (todos opcionais exceto obrigatórios)
        $dados = [
            'name'            => null,  // required
            'email'           => null,  // required
            'cpf'             => null,  // required
            'birth_date'      => null,  // nullable
            'cellphone'       => null,  // nullable
            'company_name'    => null,  // nullable
            'expiration_date' => null,  // nullable
            'password'        => null,  // opcional (gera se não informar)
            'newsletter'      => false, // boolean
            'sms'             => false, // boolean
            'whatsapp'        => false, // boolean
            'authorized'      => true,  // boolean (true = cadastra automaticamente)
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

        // Remove campos nulos (exceto booleanos já definidos)
        return array_filter($dados, function ($v, $k) {
            // Mantém booleanos mesmo se false
            if (in_array($k, ['newsletter', 'sms', 'whatsapp', 'authorized'])) {
                return true;
            }
            return $v !== null && $v !== '';
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Identifica qual campo da API pelo label
     */
    private function identificarCampoClube($field): ?string
    {
        $label = mb_strtolower(trim($field->label));
        $type  = $field->type;

        // Mapeamento exato baseado nos labels comuns
        return match (true) {
            // Nome
            str_contains($label, 'nome') && str_contains($label, 'completo')                     => 'name',

            // Email
            str_contains($label, 'e-mail') || str_contains($label, 'email') || $type === 'email' => 'email',

            // CPF
            str_contains($label, 'cpf')                                                          => 'cpf',

            // Data de nascimento
            str_contains($label, 'nascimento') ||
            (str_contains($label, 'data') && str_contains($label, 'nasc')) ||
            ($type === 'date' && str_contains($label, 'nasc'))                                   => 'birth_date',

            // Celular
            str_contains($label, 'celular') ||
            str_contains($label, 'telefone') ||
            str_contains($label, 'fone') ||
            str_contains($label, 'tel') ||
            str_contains($label, 'whatsapp')                                                     => 'cellphone',

            // Empresa
            str_contains($label, 'empresa') ||
            str_contains($label, 'company') ||
            str_contains($label, 'organização')                                                  => 'company_name',

            // Data de expiração
            str_contains($label, 'expiração') ||
            str_contains($label, 'validade') ||
            str_contains($label, 'vencimento')                                                   => 'expiration_date',

            // Senha
            str_contains($label, 'senha') ||
            str_contains($label, 'password')                                                     => 'password',

            default                                                                              => null,
        };
    }

    /**
     * Formata valor conforme regras da API
     */
    private function formatarValor(mixed $valor, string $campoClube, string $fieldType): mixed
    {
        $valor = is_string($valor) ? trim($valor) : $valor;

        return match ($campoClube) {
            // name: string, max 100
            'name'            => mb_substr($valor, 0, 100),

            // email: string, min 10, max 80
            'email'           => mb_strtolower(mb_substr($valor, 0, 80)),

            // cpf: string, max 11 (somente números)
            'cpf'             => mb_substr(preg_replace('/[^0-9]/', '', $valor), 0, 11),

            // birth_date: string, formato 01/01/2000
            'birth_date'      => $this->formatarData($valor),

            // cellphone: string, max 15, formato (99) 99999-9999
            'cellphone'       => $this->formatarTelefone($valor),

            // company_name: string, nullable
            'company_name'    => mb_substr($valor, 0, 255) ?: null,

            // expiration_date: string, nullable
            'expiration_date' => $this->formatarData($valor),

            // password: string
            'password'        => $valor,

            // booleanos
            'newsletter', 'sms', 'whatsapp', 'authorized' => (bool) $valor,

            default           => $valor,
        };
    }

    /**
     * Formata data para 01/01/2000
     */
    private function formatarData(?string $data): ?string
    {
        if (! $data) {
            return null;
        }

        $data = trim($data);

        // Já está no formato brasileiro?
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data)) {
            return $data;
        }

        // Formato ISO (YYYY-MM-DD)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $data)->format('d/m/Y');
        }

        // Timestamp ou outro formato
        try {
            return \Carbon\Carbon::parse($data)->format('d/m/Y');
        } catch (\Exception $e) {
            Log::warning('Não foi possível formatar data', ['data' => $data]);
            return $data;
        }
    }

    /**
     * Formata telefone para (99) 99999-9999
     */
    private function formatarTelefone(?string $tel): ?string
    {
        if (! $tel) {
            return null;
        }

        // Remove tudo exceto números
        $n = preg_replace('/[^0-9]/', '', $tel);

        // Celular com 9 dígitos: (99) 99999-9999
        if (strlen($n) === 11) {
            return '(' . substr($n, 0, 2) . ') ' . substr($n, 2, 5) . '-' . substr($n, 7);
        }

        // Fixo com 8 dígitos: (99) 9999-9999
        if (strlen($n) === 10) {
            return '(' . substr($n, 0, 2) . ') ' . substr($n, 2, 4) . '-' . substr($n, 6);
        }

        // Se não tiver formato esperado, retorna como veio (limitado a 15 chars)
        return mb_substr($tel, 0, 15);
    }
}