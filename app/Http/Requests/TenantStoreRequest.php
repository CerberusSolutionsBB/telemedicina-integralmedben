<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TenantStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|unique:domains,domain',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'bg_color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'button_color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'cep' => 'nullable|string|max:9',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:2',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do tenant é obrigatório',
            'subdomain.required' => 'O domínio é obrigatório',
            'subdomain.unique' => 'Este domínio já está em uso',
            'photo.image' => 'O arquivo deve ser uma imagem',
            'photo.mimes' => 'A imagem deve ser JPG, JPEG, PNG ou WebP',
            'photo.max' => 'A imagem deve ter no máximo 2MB',
            'bg_color.regex' => 'A cor deve estar no formato hexadecimal (#RRGGBB)',
            'button_color.regex' => 'A cor do botão deve estar no formato hexadecimal (#RRGGBB)',
        ];
    }
}
