<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * Processo: campos comuns (título, descrição, ícone, status) + valor e os
 * serviços vinculados (opcionais).
 */
class ProcessoRequest extends CadastroIconeRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'valor' => ['required', 'numeric', 'min:0', 'max:99999999'],
            // Fora do formulário: será ligado pelos campos personalizados (fatia 3).
            'campo_status' => ['sometimes', 'boolean'],
            'servicos' => ['nullable', 'array'],
            // Tabela do banco do tenant (conexão padrão no contexto do tenant).
            'servicos.*' => ['integer', 'distinct', Rule::exists('servicos', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'servicos.*.exists' => 'Um dos serviços escolhidos não existe mais. Abra a lista e escolha de novo.',
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'valor' => 'valor',
            'campo_status' => 'campos personalizados',
            'servicos' => 'serviços',
            'servicos.*' => 'serviço',
        ];
    }
}
