<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleVisibilityFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('forms.edit');
    }

    public function rules(): array
    {
        return [
            'form_id' => 'required|exists:forms,id',
            'is_public' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'form_id.required' => 'O id do formulário é obrigatório.',
            'form_id.exists' => 'O formulário não existe.',
            'is_public.required' => 'O status de visibilidade é obrigatório.',
            'is_public.boolean' => 'O campo visibilidade deve ser verdadeiro ou falso.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_public' => $this->boolean('is_public'),
        ]);
    }
}
