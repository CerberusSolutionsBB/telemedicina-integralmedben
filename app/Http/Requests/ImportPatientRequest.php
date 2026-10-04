<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Nenhum arquivo enviado.',
            'file.file' => 'O arquivo enviado é inválido.',
            'file.mimes' => 'Envie a planilha em Excel (.xlsx ou .xls).',
            'file.max' => 'O arquivo não pode ser maior que 10MB.',
        ];
    }
}
