<?php

namespace App\Http\Requests\Acl;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => mb_strtolower(trim((string) $this->input('name')))]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:150',
                // Padrão modulo.acao (ex.: pacientes.view, forms.update.status)
                'regex:/^[a-z0-9_-]+(\.[a-z0-9_-]+)+$/',
                Rule::unique('permissions', 'name')->where('guard_name', 'web')->ignore($this->route('permission')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da permissão é obrigatório.',
            'name.regex' => 'Use o formato modulo.acao (ex.: pacientes.view), apenas letras minúsculas, números, "-" e "_".',
            'name.unique' => 'Esta permissão já existe.',
        ];
    }
}
