<?php

namespace App\Http\Requests;

use App\Models\Desempenho;
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
            'funcao' => ['required', Rule::in(array_keys(Desempenho::FUNCOES))],
            'escopo_plano' => ['required', Rule::in([Desempenho::ESCOPO_TODOS, Desempenho::ESCOPO_PLANO])],
            'cod_plano' => ['nullable', 'required_if:escopo_plano,'.Desempenho::ESCOPO_PLANO, Rule::in(Planos::codigos())],
            'tipo_meta' => ['required', Rule::in(array_keys(Desempenho::TIPOS))],
            'meta' => ['required', 'integer', 'min:1', 'max:1000000'],
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
            'cod_plano.required_if' => 'Escolha o plano.',
            'meta.required' => 'Informe a meta.',
            'meta.min' => 'A meta deve ser no mínimo 1.',
            'data_inicio.required' => 'Informe a data de início.',
            'prazo.required' => 'Informe o prazo.',
            'prazo.after_or_equal' => 'O prazo deve ser igual ou posterior à data de início.',
        ];
    }

    /**
     * Dados para gravar: sem plano quando o escopo é "todos".
     */
    public function dados(): array
    {
        $dados = collect($this->validated())->except('roles')->all();

        if ($dados['escopo_plano'] === Desempenho::ESCOPO_TODOS) {
            $dados['cod_plano'] = null;
        }

        return $dados;
    }
}
