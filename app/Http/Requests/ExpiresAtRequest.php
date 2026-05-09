<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpiresAtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    protected function prepareForValidation(): void
    {
        if (! $this->filled('expires_at')) {
            return;
        }
        $expiresAt = $this->input('expires_at');
        if (str_contains($expiresAt, 'T')) {
            $expiresAt = str_replace('T', ' ', $expiresAt) . ':00';
        }
        $this->merge([
            'expires_at' => $expiresAt,
        ]);
    }
    public function rules(): array
    {
        return [
            'expires_at' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
                'after:' . now()->format('Y-m-d H:i:s'),
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'expires_at.date_format' => 'A data de expiração deve estar em um formato válido.',
            'expires_at.after'       => 'A data de expiração deve ser maior que a data atual.',
        ];
    }
}