<?php
namespace App\Http\Requests\Siprov;

use Illuminate\Foundation\Http\FormRequest;

class CreateSiprovIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigoIntegracao'   => ['required', 'string', 'max:255'],
            'nomePessoa'         => ['required', 'string', 'max:255'],
            'cpfCnpj'            => ['required', 'string', 'max:20'],
            'email'              => ['required', 'email', 'max:255'],
            'sexo'               => ['required', 'string', 'in:M,F'],
            'dataNascimento'     => ['nullable', 'date_format:Y-m-d'],

            'telefones'          => ['required', 'array', 'min:1'],
            'telefones.*.numero' => ['required', 'string', 'max:20'],

            'plano'              => ['required', 'string', 'in:clinica_familiar,clinica_individual,saude_mental'],

            'ativo'              => ['nullable', 'boolean'],
            'diaVencimento'      => ['nullable', 'integer', 'between:1,31'],
            'situacao'           => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigoIntegracao.required' => 'O código de integração é obrigatório.',
            'nomePessoa.required'       => 'O nome da pessoa é obrigatório.',
            'cpfCnpj.required'          => 'O CPF/CNPJ é obrigatório.',
            'email.required'            => 'O e-mail é obrigatório.',
            'email.email'               => 'Informe um e-mail válido.',
            'sexo.required'             => 'O sexo é obrigatório.',
            'sexo.in'                   => 'O sexo deve ser M ou F.',
            'telefones.required'        => 'Informe pelo menos um telefone.',
            'plano.required'            => 'O plano é obrigatório.',
            'plano.in'                  => 'Plano SIPROV inválido.',
        ];
    }
}