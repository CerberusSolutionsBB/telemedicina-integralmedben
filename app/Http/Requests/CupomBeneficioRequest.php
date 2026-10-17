<?php

namespace App\Http\Requests;

use App\Models\CupomBeneficio;
use App\Support\Planos;
use Illuminate\Validation\Rule;

/**
 * Cupom de benefício: campos comuns (título, descrição, ícone, status) + desconto,
 * prazo e vínculos (convênio ou serviço obrigatório; plano opcional).
 */
class CupomBeneficioRequest extends CadastroIconeRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('codigo')) {
            $this->merge(['codigo' => CupomBeneficio::normalizarCodigo($this->input('codigo'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            // Na criação, vazio = gerado ao salvar; na edição é obrigatório (o id vem da rota).
            // Único no banco do parceiro.
            'codigo' => [
                $this->route('id') ? 'required' : 'nullable', 'string', 'min:4', 'max:20', 'regex:/^[A-Z0-9-]+$/',
                Rule::unique('cupom_beneficios', 'codigo')->ignore($this->route('id')),
            ],
            'cod_plano' => ['nullable', 'string', Rule::in(Planos::codigos())],
            // Tabelas do banco do tenant (conexão padrão no contexto do tenant).
            'convenio_id' => ['nullable', 'integer', 'required_without:servico_id', Rule::exists('convenios', 'id')],
            'servico_id' => ['nullable', 'integer', 'required_without:convenio_id', Rule::exists('servicos', 'id')],
            'tipo_desconto' => ['required', Rule::in(array_keys(CupomBeneficio::TIPOS))],
            'valor_desconto' => [
                'required', 'numeric', 'min:0.01',
                'max:'.($this->input('tipo_desconto') === CupomBeneficio::TIPO_PERCENTUAL ? 100 : 99999999),
            ],
            'prazo' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'codigo.regex' => 'Use só letras, números e hífen (sem espaços nem acentos).',
            'codigo.unique' => 'Já existe um cupom com este código. Clique em "Gerar outro" ou escolha outro.',
            'codigo.min' => 'O código precisa ter pelo menos 4 caracteres.',
            'codigo.required' => 'Informe o código do cupom ou clique em "Gerar outro".',
            'convenio_id.required_without' => 'Escolha um convênio ou um serviço.',
            'servico_id.required_without' => 'Escolha um convênio ou um serviço.',
            'valor_desconto.max' => 'O percentual não pode passar de 100%.',
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'codigo' => 'código',
            'cod_plano' => 'plano',
            'convenio_id' => 'convênio',
            'servico_id' => 'serviço',
            'tipo_desconto' => 'tipo de desconto',
            'valor_desconto' => 'desconto',
        ];
    }
}
