<?php
namespace App\Http\Requests\Lei;

use App\Models\Lei;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeiRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Opcional: verificar se usuário é o autor ou tem permissão
        // return $this->user()->can('update', $this->route('lei'));
        return true;
    }

    public function rules(): array
    {
        return [
            'title'  => ['required', 'string', 'min:3', 'max:255'],
            'text'   => ['required', 'string', 'min:10'],
            'type'   => ['required', 'string', Rule::in(Lei::TIPOS_VALIDOS)],
            'status' => ['required', 'string', 'in:rascunho,ativo,inativo'],
            // 'user_id' => ['nullable', 'integer', 'exists:users,id'], // opcional
        ];
    }

    protected function prepareForValidation(): void
    {
        // Não preenche user_id no update (mantém o original)
        // ou descomente para permitir alteração de autor:
        // if (! $this->has('user_id') && auth()->check()) {
        //     $this->merge(['user_id' => auth()->id()]);
        // }
    }

    public function messages(): array
    {
        return [
            'title.required'  => 'O título da lei é obrigatório.',
            'title.min'       => 'O título deve ter pelo menos :min caracteres.',
            'title.max'       => 'O título não pode exceder :max caracteres.',
            'text.required'   => 'O texto da lei é obrigatório.',
            'text.min'        => 'O texto deve ter pelo menos :min caracteres.',
            'type.required'   => 'O tipo de documento é obrigatório.',
            'type.in'         => 'O tipo deve ser um dos seguintes: ' . implode(', ', Lei::TIPOS_VALIDOS),
            'status.required' => 'O status é obrigatório.',
            'status.in'       => 'O status deve ser: rascunho, ativo ou inativo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title'  => 'título',
            'text'   => 'conteúdo',
            'type'   => 'tipo de documento',
            'status' => 'situação',
        ];
    }
}