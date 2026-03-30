<?php
namespace App\Http\Requests\Form;

use Illuminate\Foundation\Http\FormRequest;

class FormCategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'O nome da categoria é obrigatório.',
            'name.max'        => 'O nome não pode ter mais que 255 caracteres.',
            'description.max' => 'A descrição não pode ter mais que 500 caracteres.',
        ];
    }
}