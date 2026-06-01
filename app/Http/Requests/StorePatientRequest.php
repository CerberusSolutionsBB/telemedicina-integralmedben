<?php

namespace App\Http\Requests;

use App\Enums\PatientSexoEnum;
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
            'cpf' => 'nullable|string|max:14',
            'rg' => 'nullable|string|max:20',
            'data_nascimento' => 'nullable|date',
            'sexo' => ['nullable', 'string', Rule::enum(PatientSexoEnum::class)],
            'email' => 'nullable|email|max:255',
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
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do paciente é obrigatório.',
            'sexo.in' => 'O sexo deve ser masculino ou feminino.',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }
}
