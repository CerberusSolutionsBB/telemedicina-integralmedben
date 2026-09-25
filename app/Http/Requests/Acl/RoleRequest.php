<?php

namespace App\Http\Requests\Acl;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'min:2', 'max:100',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($this->route('role')),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do perfil é obrigatório.',
            'name.unique' => 'Já existe um perfil com este nome.',
            'permissions.*.exists' => 'Permissão inválida.',
        ];
    }
}
