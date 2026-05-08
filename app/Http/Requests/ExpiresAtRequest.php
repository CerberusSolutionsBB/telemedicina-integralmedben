<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpiresAtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'expires_at' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'expires_at.date' => 'A data de expiração deve ser uma data válida.',
            'expires_at.after' => 'A data de expiração deve ser maior que a data atual.',
        ];
    }
}
