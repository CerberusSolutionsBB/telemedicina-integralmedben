<?php
namespace App\Http\Requests\Siprov;

use Illuminate\Foundation\Http\FormRequest;

class CreateSiprovIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $cpfCnpj = $this->onlyNumbers($this->input('cpfCnpj'));

        $telefones = collect($this->input('telefones', []))
            ->map(function ($telefone) {
                return [
                    'ddi'    => (int) ($telefone['ddi'] ?? 55),
                    'numero' => $this->onlyNumbers($telefone['numero'] ?? ''),
                    'tipo'   => $telefone['tipo'] ?? 'Celular',
                ];
            })
            ->filter(fn($telefone) => ! empty($telefone['numero']))
            ->values()
            ->toArray();

        $this->merge([
            'cpfCnpj'          => $cpfCnpj,
            'codigoIntegracao' => 'USR-' . $cpfCnpj,
            'telefones'        => $telefones,
        ]);
    }

    public function rules(): array
    {
        return [
            'codigoIntegracao'   => ['required', 'string', 'max:255'],

            'nomePessoa'         => ['required', 'string', 'max:255'],

            'cpfCnpj'            => [
                'required',
                'string',
                'min:11',
                'max:14',
            ],

            'email'              => [
                'required',
                'email',
                'max:255',
            ],

            'sexo'               => [
                'required',
                'in:M,F,I',
            ],

            'dataNascimento'     => [
                'nullable',
                'date',
            ],

            'telefones'          => [
                'required',
                'array',
                'min:1',
            ],

            'telefones.*.ddi'    => [
                'required',
                'integer',
            ],

            'telefones.*.numero' => [
                'required',
                'string',
            ],

            'telefones.*.tipo'   => [
                'required',
                'in:Celular,Fixo',
            ],

            'plano'              => [
                'required',
                'in:clinica_familiar,clinica_individual,saude_mental',
            ],

            'diaVencimento'      => [
                'required',
                'integer',
                'between:1,31',
            ],

            'ativo'              => [
                'required',
                'boolean',
            ],

            'situacao'           => [
                'required',
                'string',
                'max:50',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nomePessoa.required'    => 'Nome é obrigatório.',
            'cpfCnpj.required'       => 'CPF/CNPJ é obrigatório.',
            'email.required'         => 'E-mail é obrigatório.',
            'sexo.required'          => 'Sexo é obrigatório.',
            'telefones.required'     => 'Informe ao menos um telefone.',
            'plano.required'         => 'Selecione um plano.',
            'diaVencimento.required' => 'Informe o dia de vencimento.',
        ];
    }

    private function onlyNumbers(?string $value): string
    {
        return preg_replace('/\D/', '', $value ?? '');
    }
}
