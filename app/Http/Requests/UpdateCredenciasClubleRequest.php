<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCredenciasClubleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $credencialId = $this->route('credencias_cluble') ?? $this->route('id');

        return [
            'title'         => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:1000',
            'scope'         => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'title'         => 'título',
            'client_secret' => 'segredo do cliente',
            'scope'         => 'escopo',
        ];
    }
}
