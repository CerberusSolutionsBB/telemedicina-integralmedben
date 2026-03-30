<?php
namespace App\Http\Requests\Lei;

use App\Models\Lei;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title'   => ['required', 'string', 'min:3', 'max:255'],
            'text'    => ['required', 'string', 'min:10'],
            'type'    => ['required', 'string', Rule::in(Lei::TIPOS_VALIDOS)],
        ];
    }
    protected function prepareForValidation(): void
    {
        if (! $this->has('user_id') && auth()->check()) {
            $this->merge(['user_id' => auth()->id()]);
        }
    }
    public function messages(): array
    {
        return [
            'title.required' => 'O título da lei é obrigatório.',
            'title.min'      => 'O título deve ter pelo menos :min caracteres.',
            'title.max'      => 'O título não pode exceder :max caracteres.',
            'text.required'  => 'O texto da lei é obrigatório.',
            'text.min'       => 'O texto deve ter pelo menos :min caracteres.',
            'type.required'  => 'O tipo de documento é obrigatório.',
            'type.in'        => 'O tipo deve ser um dos seguintes: ' . implode(', ', Lei::TIPOS_VALIDOS),
            'user_id.exists' => 'O usuário informado não existe no sistema.',
        ];
    }
    public function attributes(): array
    {
        return [
            'user_id' => 'autor',
            'title'   => 'título',
            'text'    => 'conteúdo',
            'type'    => 'tipo de documento',
        ];
    }
}