<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCredenciasClubleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'title'         => 'nullable|string|max:255',
            'grant_type'    => 'required|string|in:client_credentials,password,authorization_code,refresh_token',
            'client_id'     => [
                'required',
                'string',
                'max:255',
                Rule::unique('credencias_clubles', 'client_id')
                    ->whereNull('deleted_at'),
            ],
            'client_secret' => 'required|string|max:1000',
            'scope'         => 'nullable|string|max:500',
        ];
    }
    protected function prepareForValidation(): void
    {
        $this->merge([
            'grant_type' => $this->input('grant_type', 'client_credentials'),
            'scope'      => $this->input('scope', '*'),
        ]);
    }
    public function attributes(): array
    {
        return [
            'title'         => 'título',
            'grant_type'    => 'tipo de concessão',
            'client_id'     => 'ID do cliente',
            'client_secret' => 'segredo do cliente',
            'scope'         => 'escopo',
        ];
    }
    public function messages(): array
    {
        return [
            'client_id.unique' => 'Este Client ID já está cadastrado no sistema.',
        ];
    }
}
