<?php

namespace App\Http\Requests;

use App\Enums\PatientSexoEnum;
use App\Models\Patient;
use App\Services\Tenant\PacientePlanoService;
use App\Support\SiprovPlanos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:255',
            'cpf' => ['nullable', 'required_with:cod_plano', 'string', 'max:14'],
            'rg' => 'nullable|string|max:20',
            'data_nascimento' => 'nullable|date',
            'sexo' => ['nullable', 'string', Rule::enum(PatientSexoEnum::class)],
            'email' => ['nullable', 'required_with:cod_plano', 'email', 'max:255'],
            'numero' => 'nullable|string|max:20',
            'enderecos' => 'nullable|array',
            'enderecos.cep' => 'nullable|string|max:9',
            'enderecos.logradouro' => 'nullable|string|max:255',
            'enderecos.numero' => 'nullable|string|max:20',
            'enderecos.complemento' => 'nullable|string|max:255',
            'enderecos.bairro' => 'nullable|string|max:255',
            'enderecos.cidade' => 'nullable|string|max:255',
            'enderecos.estado' => 'nullable|string|max:2',
            'status' => 'nullable|boolean',
            'response_id' => 'nullable|integer',
            'cod_plano' => [
                Rule::requiredIf(fn () => ! $this->pacienteJaVinculado()),
                'nullable',
                'string',
                Rule::in(SiprovPlanos::codigos()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do paciente é obrigatório.',
            'sexo.in' => 'O sexo deve ser masculino ou feminino.',
            'email.email' => 'Informe um e-mail válido.',
            'cpf.required_with' => 'Informe o CPF para vincular o paciente a um plano.',
            'email.required_with' => 'Informe o e-mail para vincular o paciente a um plano.',
            'cod_plano.required' => 'Selecione o plano / telemedicina.',
            'cod_plano.in' => 'Plano inválido.',
        ];
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
