<?php

namespace App\Rules;

use App\Models\CentralPatientAnswer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueAnswerRule implements ValidationRule
{
    public function __construct(private int $questionId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value || trim((string) $value) === '') {
            return;
        }

        $normalized = preg_replace('/\D/', '', $value);

        $exists = CentralPatientAnswer::where('question_id', $this->questionId)
            ->where('answer', $normalized)
            ->exists();

        if ($exists) {
            $fail('Este paciente já foi cadastrado anteriormente.');
        }
    }
}
