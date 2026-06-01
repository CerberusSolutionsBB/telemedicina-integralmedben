<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenantFormsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'forms' => 'nullable|array',
            'forms.*' => 'integer|exists:forms,id',
        ];
    }

    public function messages(): array
    {
        return [
            'forms.array' => 'O campo formulários deve ser uma lista.',
            'forms.*.integer' => 'O Id do formulário deve ser um número inteiro.',
            'forms.*.exists' => 'O formulário selecionado não existe.',
        ];
    }

    /**
     * Prepara os dados para validação
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('forms')) {
            $this->merge(['forms' => []]);
        }
    }
}
