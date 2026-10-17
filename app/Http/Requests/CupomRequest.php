<?php

namespace App\Http\Requests;

use App\Models\Cupom;
use App\Services\Cupom\CupomService;
use App\Support\Planos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Cadastro e edição de cupom, no painel central (admin) e no do parceiro.
 * No parceiro, o percentual respeita o teto definido pelo admin.
 */
class CupomRequest extends FormRequest
{
    public const LIMITES = [
        'codigo' => 50,
        'observacoes' => 2000,
        'texto_desconto' => 120,
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('codigo')) {
            $this->merge(['codigo' => Cupom::normalizarCodigo($this->input('codigo'))]);
        }
    }

    /**
     * Cupons ficam no banco central: unique/exists usam a conexão mysql também no painel do parceiro.
     */
    public function rules(): array
    {
        $cupom = $this->cupomAtual();

        return [
            'codigo' => [
                'nullable', 'string', 'max:'.self::LIMITES['codigo'], 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('mysql.cupons', 'codigo')->ignore($cupom?->id),
            ],
            'tipo' => ['required', Rule::in(array_keys(Cupom::TIPOS))],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:'.($this->input('tipo') === Cupom::TIPO_PERCENTUAL ? 100 : 99999999)],
            'tenant_id' => [$this->ehParceiro() ? 'prohibited' : 'nullable', 'string', Rule::exists('mysql.tenants', 'id')],
            'origem' => ['required', Rule::in(array_keys(Cupom::ORIGENS))],
            'inicio_em' => ['required', 'date'],
            'expira_em' => ['required', 'date', 'after:inicio_em'],
            'limite_uso_total' => ['nullable', 'integer', 'min:1'],
            'limite_uso_por_cpf' => ['required', 'integer', 'min:1'],
            'valor_minimo' => ['nullable', 'numeric', 'min:0'],
            'aplicavel_a' => ['required', Rule::in(array_keys(Cupom::APLICAVEIS))],
            'planos' => ['nullable', 'array', 'required_if:aplicavel_a,'.Cupom::APLICAVEL_PLANOS],
            'planos.*' => ['string', Rule::in(Planos::codigos())],
            'observacoes' => ['nullable', 'string', 'max:'.self::LIMITES['observacoes']],
            // Texto abaixo do desconto no PDF (o que o cliente vê); vazio = texto padrão.
            'texto_desconto' => ['nullable', 'string', 'max:'.self::LIMITES['texto_desconto']],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.regex' => 'Use só letras, números, hífen e sublinhado.',
            'codigo.unique' => 'Já existe um cupom com este código.',
            'valor.max' => 'O percentual não pode passar de 100%.',
            'expira_em.after' => 'A expiração precisa ser depois do início.',
            'planos.required_if' => 'Escolha ao menos um plano.',
        ];
    }

    public function attributes(): array
    {
        return [
            'inicio_em' => 'início',
            'expira_em' => 'expiração',
            'limite_uso_total' => 'limite total de usos',
            'limite_uso_por_cpf' => 'limite por CPF',
            'valor_minimo' => 'valor mínimo',
            'texto_desconto' => 'texto do desconto',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $cupom = $this->cupomAtual();

                // Depois do primeiro uso, o que define o desconto não muda (o histórico ficaria incoerente).
                if ($cupom instanceof Cupom && $cupom->usos > 0) {
                    foreach (['codigo' => $cupom->codigo, 'tipo' => $cupom->tipo, 'valor' => (float) $cupom->valor] as $campo => $atual) {
                        $novo = $campo === 'valor' ? (float) $this->input($campo) : $this->input($campo);

                        if ($this->has($campo) && $novo != $atual) {
                            $validator->errors()->add($campo, 'Não pode ser alterado depois que o cupom foi usado.');
                        }
                    }
                }

                if ($this->ehParceiro() && $this->input('tipo') === Cupom::TIPO_PERCENTUAL) {
                    $teto = app(CupomService::class)->tetoDoTenant((string) tenant('id'));

                    if ($teto !== null && (float) $this->input('valor') > $teto) {
                        $validator->errors()->add('valor', 'O desconto máximo permitido é de '.rtrim(rtrim(number_format($teto, 2, ',', ''), '0'), ',').'%.');
                    }
                }
            },
        ];
    }

    /**
     * No painel central a rota injeta o model; no do parceiro, só o id.
     */
    private function cupomAtual(): ?Cupom
    {
        $parametro = $this->route('cupom');

        return $parametro instanceof Cupom ? $parametro : ($parametro ? Cupom::find($parametro) : null);
    }

    private function ehParceiro(): bool
    {
        return tenancy()->initialized;
    }
}
