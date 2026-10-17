<?php

namespace App\Http\Requests;

use App\Models\Desempenho;
use App\Rules\UsuarioDosPerfis;
use App\Support\Planos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DesempenhoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:'.Desempenho::LIMITES['titulo']],
            'descricao' => ['nullable', 'string', 'max:'.Desempenho::LIMITES['descricao']],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
            'usuarios' => ['array'],
            'usuarios.*' => ['integer', Rule::exists('users', 'id'), new UsuarioDosPerfis],
            'funcao' => ['required', Rule::in(array_keys(Desempenho::FUNCOES))],
            'escopo_plano' => ['required', Rule::in([Desempenho::ESCOPO_TODOS, Desempenho::ESCOPO_PLANO])],
            'cod_planos' => ['nullable', 'array', 'required_if:escopo_plano,'.Desempenho::ESCOPO_PLANO],
            'cod_planos.*' => ['string', 'distinct', Rule::in(Planos::codigos())],
            'tipo_meta' => ['required', Rule::in(array_keys(Desempenho::TIPOS))],
            'meta' => ['required', 'integer', 'min:1', 'max:1000000'],
            'meta_valor' => [
                'nullable', 'numeric', 'min:0', 'max:999999999.99',
                'required_if:funcao,'.Desempenho::FUNCAO_COMISSAO_VENDA_PLANO,
            ],
            'data_inicio' => ['required', 'date'],
            'prazo' => ['required', 'date', 'after_or_equal:data_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'Informe o título.',
            'titulo.max' => 'O título pode ter no máximo :max caracteres.',
            'descricao.max' => 'A descrição pode ter no máximo :max caracteres.',
            'roles.required' => 'Escolha ao menos um perfil de usuário.',
            'roles.min' => 'Escolha ao menos um perfil de usuário.',
            'usuarios.array' => 'Seleção de usuários inválida.',
            'usuarios.*.integer' => 'Seleção de usuários inválida.',
            'usuarios.*.exists' => 'Um dos usuários selecionados não existe mais.',
            'cod_planos.required_if' => 'Escolha pelo menos um plano.',
            'meta.required' => 'Informe a meta.',
            'meta.min' => 'A meta deve ser no mínimo 1.',
            'meta_valor.required_if' => 'Informe a meta em R$ (comissão).',
            'meta_valor.numeric' => 'Informe uma meta em R$ válida.',
            'meta_valor.min' => 'A meta em R$ não pode ser negativa.',
            'data_inicio.required' => 'Informe a data de início.',
            'prazo.required' => 'Informe o prazo.',
            'prazo.after_or_equal' => 'O prazo deve ser igual ou posterior à data de início.',
        ];
    }

    /**
     * Dados para gravar: sem lista de planos quando o escopo é "todos".
     */
    public function dados(): array
    {
        $dados = collect($this->validated())->except(['roles', 'usuarios'])->all();

        $dados['cod_planos'] = $dados['escopo_plano'] === Desempenho::ESCOPO_TODOS
            ? null
            : array_values(array_unique(array_map('strval', $dados['cod_planos'] ?? [])));

        return $dados;
    }
}
