<?php
namespace App\Http\Requests\Form;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FormCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
                                                                                                                    // Tenta pegar o ID de várias formas possíveis (depende de como nomeou a rota)
        $categoryId = $this->route('id') ?? $this->route('category') ?? $this->route('form_category') ?? $this->id; // Se vier como parâmetro na URL ou body

        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'slug'        => [
                'nullable',
                'string',
                'max:150',
                // O segredo está aqui: ignore($categoryId)
                Rule::unique('form_categories', 'slug')->ignore($categoryId),
            ],
            'sort_order'  => 'nullable|integer|min:0',
            'color'       => 'nullable|string|max:25',
            'icon'        => 'nullable|string|max:50',
            'status'      => 'nullable|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Se enviou slug manualmente, sanitiza
        if ($this->has('slug') && $this->slug) {
            $this->merge(['slug' => Str::slug($this->slug)]);
        }
        // Se não enviou slug mas enviou nome, gera do nome
        elseif ($this->name && empty($this->slug)) {
            $this->merge(['slug' => Str::slug($this->name)]);
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da categoria é obrigatório.',
            'slug.unique'   => 'Este identificador (slug) já está em uso por outra categoria.',
        ];
    }
}