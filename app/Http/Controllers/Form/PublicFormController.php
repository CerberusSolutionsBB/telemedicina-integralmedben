<?php
namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormResponse;
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
    private function canAcceptResponses(Form $form): bool
    {
        return $form->status === 'ativo';
    }
    public function show(string $slug): Response
    {
        try {
            $form = Form::where('slug', $slug)
                ->where('is_public', true)
                ->with(['fields' => fn($q) => $q->orderBy('order')])
                ->first();
            if (! $form) {
                Log::warning('Formulário não encontrado', [
                    'slug' => $slug,
                    'ip'   => request()->ip(),
                ]);
                abort(404, 'Formulário não encontrado.');
            }
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
                        'title'       => $form->title,
                        'status'      => $form->status,
                        'statusLabel' => $label,
                        'message'     => "Este formulário está {$label} e não pode receber respostas no momento.",
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
                        'title'       => $form->title,
                        'slug'        => $slug,
                        'status'      => 'expirado',
                        'statusLabel' => 'expirado',
                        'message'     => 'Este formulário expirou e não está mais disponível para respostas.',
                        'instruction' => 'Renove a data de expiração nas configurações para reativá-lo.',
                        'canActivate' => false,
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
                        'title'       => $form->title,
                        'status'      => 'limite_atingido',
                        'statusLabel' => 'com limite atingido',
                        'message'     => 'Este formulário atingiu o limite máximo de respostas.',
                        'instruction' => 'Aumente o limite de respostas nas configurações ou crie um novo formulário.',
                        'canActivate' => false,
                    ],
                ]);
            }
            return Inertia::render('Form/Public/Show', [
                'form' => [
                    'id'          => $form->id,
                    'title'       => $form->title,
                    'slug'        => $slug,
                    'description' => $form->description,
                    'status'      => $form->status,
                    'fields'      => $form->fields->map(fn($f) => [
                        'id'          => $f->id,
                        'type'        => $f->type,
                        'label'       => $f->label,
                        'placeholder' => $f->placeholder,
                        'required'    => $f->required,
                        'options'     => $f->options,
                        'help_text'   => $f->help_text,
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
                        $fieldRules[] = 'date';
                        break;
                }
                $rules["answers.{$field->id}"] = $fieldRules;
            }
            $validated = $request->validate($rules, $messages);
            FormResponse::create([
                'form_id'    => $form->id,
                'user_id'    => auth()->id(),
                'answers'    => $validated['answers'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            $form->increment('responses_count');
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
                'title'       => $form->title,
                'description' => $form->description,
            ],
        ]);
    }
}