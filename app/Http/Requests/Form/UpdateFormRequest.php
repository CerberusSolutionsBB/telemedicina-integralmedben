<?php
namespace App\Http\Requests\Form;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $form = $this->route('form');

        return $this->user()->can('forms.edit') || $form->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'title'                => 'required|string|max:255',
            'description'          => 'nullable|string',
            'categoria_id'         => 'nullable|exists:form_categories,id',
            'lei_id'               => 'nullable|exists:leis,id',
            'status'               => 'required|in:rascunho,ativo,pausado,encerrado',
            'is_public'            => 'boolean',
            'published_at'         => 'nullable|date',
            'expires_at'           => 'nullable|date|after_or_equal:published_at',
            'response_limit'       => 'nullable|integer|min:1',
            'fields'               => 'required|array|min:1',
            'fields.*.id'          => 'nullable',
            'fields.*.type'        => 'required|in:text,textarea,email,number,date,select,checkbox,radio',
            'fields.*.label'       => 'required|string|max:255',
            'fields.*.placeholder' => 'nullable|string|max:255',
            'fields.*.required'    => 'boolean',
            'fields.*.options'     => 'required_if:fields.*.type,select,checkbox,radio|array',
            'fields.*.help_text'   => 'nullable|string|max:500',
            'fields.*.order'       => 'required|integer|min:0',
            'settings'             => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'               => 'O título do formulário é obrigatório.',
            'title.max'                    => 'O título não pode ter mais de 255 caracteres.',
            'status.required'              => 'O status do formulário é obrigatório.',
            'status.in'                    => 'O status selecionado é inválido.',
            'expires_at.after_or_equal'    => 'A data de expiração deve ser igual ou posterior à data de publicação.',
            'fields.required'              => 'O formulário deve ter pelo menos um campo.',
            'fields.min'                   => 'O formulário deve ter pelo menos um campo.',
            'fields.*.type.required'       => 'O tipo do campo é obrigatório.',
            'fields.*.type.in'             => 'O tipo de campo selecionado é inválido.',
            'fields.*.label.required'      => 'O rótulo do campo é obrigatório.',
            'fields.*.label.max'           => 'O rótulo não pode ter mais de 255 caracteres.',
            'fields.*.options.required_if' => 'Campos do tipo select, checkbox ou radio precisam ter opções.',
            'fields.*.order.required'      => 'A ordem do campo é obrigatória.',
            'categoria_id.exists'          => 'A categoria selecionada não existe.',
            'lei_id.exists'                => 'A lei selecionada não existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title'                => 'título',
            'description'          => 'descrição',
            'categoria_id'         => 'categoria',
            'lei_id'               => 'lei',
            'status'               => 'status',
            'is_public'            => 'público',
            'published_at'         => 'data de publicação',
            'expires_at'           => 'data de expiração',
            'response_limit'       => 'limite de respostas',
            'fields'               => 'campos',
            'fields.*.type'        => 'tipo do campo',
            'fields.*.label'       => 'rótulo do campo',
            'fields.*.placeholder' => 'placeholder',
            'fields.*.required'    => 'obrigatório',
            'fields.*.options'     => 'opções',
            'fields.*.help_text'   => 'texto de ajuda',
            'fields.*.order'       => 'ordem',
            'settings'             => 'configurações',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_public')) {
            $this->merge([
                'is_public' => filter_var($this->is_public, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('fields') && is_array($this->fields)) {
            $fields = collect($this->fields)->map(function ($field) {
                if (isset($field['required'])) {
                    $field['required'] = filter_var($field['required'], FILTER_VALIDATE_BOOLEAN);
                }
                return $field;
            })->toArray();

            $this->merge(['fields' => $fields]);
        }
    }
}