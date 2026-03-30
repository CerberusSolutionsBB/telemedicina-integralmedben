<?php
namespace App\Http\Requests\Form;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class StoreFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('forms.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'title'                => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'categoria_id'         => ['nullable', 'integer', 'exists:form_categories,id'],
            'lei_id'               => ['nullable', 'integer', 'exists:leis,id'],
            'status'               => ['required', Rule::in(['rascunho', 'ativo', 'pausado', 'encerrado'])],
            'is_public'            => ['nullable', 'boolean'],
            'published_at'         => ['nullable', 'date'],
            'expires_at'           => ['nullable', 'date', 'after_or_equal:published_at'],
            'response_limit'       => ['nullable', 'integer', 'min:1'],
            'settings'             => ['nullable', 'array'],

            // Validação do array fields
            'fields'               => ['required', 'array', 'min:1'],
            'fields.*.type'        => ['required', Rule::in(['text', 'textarea', 'email', 'number', 'date', 'select', 'checkbox', 'radio'])],
            'fields.*.label'       => ['required', 'string', 'max:255'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.required'    => ['nullable', 'boolean'],
            'fields.*.options'     => ['nullable', 'array'],
            'fields.*.options.*'   => ['nullable', 'string'],
            'fields.*.help_text'   => ['nullable', 'string', 'max:500'],
            'fields.*.order'       => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'            => 'O título do formulário é obrigatório.',
            'title.max'                 => 'O título não pode ter mais de 255 caracteres.',

            'categoria_id.integer'      => 'A categoria selecionada é inválida.',
            'categoria_id.exists'       => 'A categoria selecionada não existe.',

            'lei_id.integer'            => 'A lei selecionada é inválida.',
            'lei_id.exists'             => 'A lei selecionada não existe.',

            'status.required'           => 'O status do formulário é obrigatório.',
            'status.in'                 => 'O status selecionado é inválido.',

            'published_at.date'         => 'A data de publicação deve ser uma data válida.',
            'expires_at.date'           => 'A data de expiração deve ser uma data válida.',
            'expires_at.after_or_equal' => 'A data de expiração deve ser igual ou posterior à data de publicação.',

            'response_limit.integer'    => 'O limite de respostas deve ser um número inteiro.',
            'response_limit.min'        => 'O limite de respostas deve ser pelo menos 1.',

            'fields.required'           => 'Adicione pelo menos um campo ao formulário.',
            'fields.array'              => 'Os campos devem ser enviados como uma lista.',
            'fields.min'                => 'Adicione pelo menos um campo ao formulário.',

            'fields.*.type.required'    => 'O tipo do campo #:position é obrigatório.',
            'fields.*.type.in'          => 'O tipo do campo #:position é inválido.',

            'fields.*.label.required'   => 'A pergunta do campo #:position é obrigatória.',
            'fields.*.label.max'        => 'A pergunta do campo #:position não pode ter mais de 255 caracteres.',

            'fields.*.placeholder.max'  => 'O placeholder do campo #:position é muito longo.',
            'fields.*.help_text.max'    => 'A descrição do campo #:position não pode ter mais de 500 caracteres.',
        ];
    }

    /**
     * Preparar dados antes da validação
     */
    protected function prepareForValidation(): void
    {
        // Garantir que is_public seja boolean
        if ($this->has('is_public')) {
            $this->merge([
                'is_public' => filter_var($this->is_public, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Converter categoria_id e lei_id para int ou null
        if ($this->has('categoria_id')) {
            $this->merge([
                'categoria_id' => $this->categoria_id ? (int) $this->categoria_id : null,
            ]);
        }

        if ($this->has('lei_id')) {
            $this->merge([
                'lei_id' => $this->lei_id ? (int) $this->lei_id : null,
            ]);
        }

        // Se fields vier como string JSON, decodificar
        if ($this->has('fields') && is_string($this->fields)) {
            $decoded = json_decode($this->fields, true);
            $this->merge([
                'fields' => is_array($decoded) ? $decoded : [],
            ]);
        }

        // Limpar e normalizar os campos
        if ($this->has('fields') && is_array($this->fields)) {
            $cleanedFields = array_map(function ($field, $index) {
                return [
                    'type'        => $field['type'] ?? 'text',
                    'label'       => isset($field['label']) ? trim($field['label']) : '',
                    'placeholder' => isset($field['placeholder']) ? trim($field['placeholder']) : null,
                    'required'    => filter_var($field['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'options'     => $this->cleanOptions($field['options'] ?? [], $field['type'] ?? 'text'),
                    'help_text'   => isset($field['help_text']) ? trim($field['help_text']) : null,
                    'order'       => $index,
                ];
            }, $this->fields, array_keys($this->fields));

            $this->merge(['fields' => $cleanedFields]);
        }
    }

    /**
     * Limpar opções vazias
     */
    private function cleanOptions(array $options, string $type): array
    {
        if (! in_array($type, ['select', 'checkbox', 'radio'])) {
            return [];
        }

        return array_values(array_filter($options, function ($opt) {
            return ! empty($opt) && is_string($opt) && trim($opt) !== '';
        }));
    }

    /**
     * Personalizar atributos para mensagens de erro
     */
    public function attributes(): array
    {
        $attributes = [
            'title'          => 'título',
            'description'    => 'descrição',
            'categoria_id'   => 'categoria',
            'lei_id'         => 'base legal',
            'status'         => 'status',
            'is_public'      => 'visibilidade pública',
            'published_at'   => 'data de publicação',
            'expires_at'     => 'data de expiração',
            'response_limit' => 'limite de respostas',
            'settings'       => 'configurações',
        ];

        // Adicionar atributos dinâmicos para cada campo
        $fields = $this->input('fields', []);
        foreach ($fields as $index => $field) {
            $num                                       = $index + 1;
            $attributes["fields.{$index}.type"]        = "tipo do campo {$num}";
            $attributes["fields.{$index}.label"]       = "pergunta do campo {$num}";
            $attributes["fields.{$index}.placeholder"] = "placeholder do campo {$num}";
            $attributes["fields.{$index}.required"]    = "obrigatório do campo {$num}";
            $attributes["fields.{$index}.options"]     = "opções do campo {$num}";
            $attributes["fields.{$index}.help_text"]   = "descrição do campo {$num}";
        }

        return $attributes;
    }

    /**
     * Substituir :position nas mensagens de erro
     */
    protected function formatErrors(Validator $validator): array
    {
        $errors    = $validator->errors()->messages();
        $formatted = [];

        foreach ($errors as $key => $messages) {
            // Extrair índice do campo se for fields.*.algo
            if (preg_match('/fields\.(\d+)\./', $key, $matches)) {
                $position        = (int) $matches[1] + 1;
                $formatted[$key] = array_map(function ($msg) use ($position) {
                    return str_replace(':position', $position, $msg);
                }, $messages);
            } else {
                $formatted[$key] = $messages;
            }
        }

        return $formatted;
    }

    /**
     * Tratamento de falha de validação para Inertia
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $this->formatErrors($validator);

        if ($this->expectsJson() || $this->header('X-Inertia')) {
            throw new HttpResponseException(
                redirect()->back()
                    ->withInput()
                    ->withErrors($errors)
            );
        }

        throw new HttpResponseException(
            Redirect::back()
                ->withInput()
                ->withErrors($errors)
        );
    }

    /**
     * Tratamento de autorização negada
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            redirect()->back()
                ->with('error', 'Você não tem permissão para criar formulários.')
        );
    }
}