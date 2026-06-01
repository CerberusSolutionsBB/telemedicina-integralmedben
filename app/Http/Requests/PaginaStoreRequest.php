<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaginaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao' => 'required|string|min:3|max:255|regex:/^[a-zA-ZÀ-ÿ\s]+$/',
            'nome' => 'required|string|min:3|max:100',
            'email' => 'required|email|max:100',
            'senha' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            'forms' => 'nullable|array',
            'forms.*' => 'integer|exists:forms,id',
        ];
    }

    public function messages(): array
    {
        return [
            'descricao.required' => 'A descrição é obrigatória.',
            'descricao.min' => 'A descrição deve ter no mínimo :min caracteres.',
            'descricao.max' => 'A descrição deve ter no máximo :max caracteres.',
            'descricao.regex' => 'A descrição não pode conter números ou caracteres especiais.',

            'nome.required' => 'O nome é obrigatório.',
            'nome.min' => 'O nome deve ter no mínimo :min caracteres.',
            'nome.max' => 'O nome deve ter no máximo :max caracteres.',

            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail deve ter no máximo :max caracteres.',

            'senha.required' => 'A senha é obrigatória.',
            'senha.min' => 'A senha deve ter no mínimo :min caracteres.',
            'senha.confirmed' => 'As senhas não conferem.',
            'senha.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma minúscula e um número.',

            'forms.array' => 'O campo formulários deve ser uma lista.',
            'forms.*.integer' => 'O ID do formulário deve ser um número inteiro.',
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
