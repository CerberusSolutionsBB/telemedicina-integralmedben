<?php

namespace App\Http\Requests;

use App\Enums\PatientSexoEnum;
use App\Models\Patient;
use App\Services\Tenant\PacientePlanoService;
use App\Support\Planos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    /** Membros da família permitidos no plano familiar. */
    public const MAX_FAMILIARES = 3;

    /** Máximo de caracteres dos campos de texto (validação e contador do formulário). */
    public const LIMITES = [
        'nome' => 255,
        'rg' => 20,
        'email' => 255,
        'logradouro' => 255,
        'numero_endereco' => 20,
        'complemento' => 255,
        'bairro' => 255,
        // SIPROV aceita nome do dependente com 3 a 100 caracteres.
        'familiar_nome' => 100,
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:'.self::LIMITES['nome'],
            'cpf' => ['nullable', 'required_with:cod_plano', 'string', 'max:14'],
            'rg' => 'nullable|string|max:'.self::LIMITES['rg'],
            'data_nascimento' => ['required', 'date', 'before_or_equal:today'],
            'sexo' => ['nullable', 'string', Rule::enum(PatientSexoEnum::class)],
            'email' => ['nullable', 'email', 'max:'.self::LIMITES['email']],
            'numero' => 'nullable|string|max:20',
            'enderecos' => 'nullable|array',
            'enderecos.cep' => 'nullable|string|max:9',
            'enderecos.logradouro' => 'nullable|string|max:'.self::LIMITES['logradouro'],
            'enderecos.numero' => 'nullable|string|max:'.self::LIMITES['numero_endereco'],
            'enderecos.complemento' => 'nullable|string|max:'.self::LIMITES['complemento'],
            'enderecos.bairro' => 'nullable|string|max:'.self::LIMITES['bairro'],
            'enderecos.cidade' => 'nullable|string|max:255',
            'enderecos.estado' => 'nullable|string|max:2',
            'status' => 'nullable|boolean',
            'response_id' => 'nullable|integer',
            // Vendedor/indicado por: usuário do tenant que recebe a comissão.
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'cod_plano' => [
                Rule::requiredIf(fn () => ! $this->pacienteJaVinculado()),
                'nullable',
                'string',
                Rule::in(Planos::codigos()),
            ],
            // Membros da família: só gravados quando o plano é o familiar.
            'familiares' => ['nullable', 'array', 'max:'.self::MAX_FAMILIARES],
            'familiares.*.id' => 'nullable|integer',
            'familiares.*.nome' => 'required|string|min:3|max:'.self::LIMITES['familiar_nome'],
            // CPF do familiar: diferente do beneficiário e dos outros familiares.
            'familiares.*.cpf' => [
                'nullable',
                'string',
                'max:14',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $cpf = self::digitos($value);

                    if ($cpf === '') {
                        return;
                    }

                    if ($cpf === self::digitos($this->input('cpf'))) {
                        $fail('O CPF do familiar deve ser diferente do CPF do beneficiário.');

                        return;
                    }

                    $repetidos = collect($this->input('familiares', []))
                        ->filter(fn ($f) => self::digitos($f['cpf'] ?? null) === $cpf)
                        ->count();

                    if ($repetidos > 1) {
                        $fail('Este CPF já foi informado para outro familiar.');
                    }
                },
            ],
            'familiares.*.data_nascimento' => ['nullable', 'date', 'before_or_equal:today'],
            // Vínculo = parentesco da SIPROV (TiposVinculoFamiliarSeeder).
            'familiares.*.tipo' => ['required', 'string', Rule::exists('tipos_vinculo_familiar', 'codigo')],
            'familiares.*.sexo' => ['required', Rule::in(['Feminino', 'Masculino'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do paciente é obrigatório.',
            'sexo.in' => 'O sexo deve ser masculino ou feminino.',
            'email.email' => 'Informe um e-mail válido.',
            'cpf.required_with' => 'Informe o CPF para vincular o paciente a um plano.',
            'data_nascimento.required' => 'Informe a data de nascimento.',
            'data_nascimento.date' => 'Informe uma data de nascimento válida.',
            'data_nascimento.before_or_equal' => 'A data de nascimento não pode estar no futuro.',
            'cod_plano.required' => 'Selecione o plano / telemedicina.',
            'cod_plano.in' => 'Plano inválido.',
            'familiares.max' => 'O plano familiar permite no máximo '.self::MAX_FAMILIARES.' membros da família.',
            'familiares.*.nome.required' => 'Informe o nome do familiar.',
            'familiares.*.nome.min' => 'O nome do familiar deve ter pelo menos 3 caracteres.',
            'familiares.*.nome.max' => 'O nome do familiar deve ter no máximo 100 caracteres.',
            'familiares.*.tipo.required' => 'Selecione o vínculo do familiar.',
            'familiares.*.tipo.exists' => 'Vínculo familiar inválido.',
            'familiares.*.sexo.required' => 'Selecione o sexo do familiar.',
            'familiares.*.sexo.in' => 'O sexo do familiar deve ser Feminino ou Masculino.',
            'familiares.*.data_nascimento.date' => 'Informe uma data de nascimento válida.',
            'familiares.*.data_nascimento.before_or_equal' => 'A data de nascimento não pode estar no futuro.',
        ];
    }

    private static function digitos(mixed $valor): string
    {
        return preg_replace('/\D/', '', (string) $valor);
    }

    /**
     * Na edição, paciente que já tem vínculo de telemedicina mantém o plano (só leitura).
     */
    private function pacienteJaVinculado(): bool
    {
        $patient = $this->route('patient');

        return $patient instanceof Patient
            && app(PacientePlanoService::class)->vinculoAtual((string) tenant('id'), $patient->cpf) !== null;
    }
}
