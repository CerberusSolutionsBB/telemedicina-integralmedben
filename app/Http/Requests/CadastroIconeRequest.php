<?php

namespace App\Http\Requests;

use App\Support\IconesLucide;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Cadastros com título, descrição, ícone (lucide) e status: serviços e convênios.
 */
class CadastroIconeRequest extends FormRequest
{
    public const LIMITES = [
        'titulo' => 120,
        'descricao' => 1000,
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:'.self::LIMITES['titulo']],
            'descricao' => ['nullable', 'string', 'max:'.self::LIMITES['descricao']],
            // Qualquer ícone do lucide (nome do componente); o front escolhe da lista completa.
            'icone' => ['required', 'string', 'max:50', IconesLucide::REGRA],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'icone.required' => 'Escolha um ícone.',
            'icone.regex' => 'Ícone inválido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'titulo' => 'título',
            'descricao' => 'descrição',
            'icone' => 'ícone',
        ];
    }
}
