<?php

namespace App\Support;

use App\Models\Question;
use Carbon\Carbon;

class PatientAnswerFormatter
{
    public static function formatAnswer(?string $answer, Question $question): string
    {
        if (! $answer) {
            return '';
        }

        if ($question->type === 'date' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $answer)) {
            return Carbon::createFromFormat('Y-m-d', $answer)->format('d/m/Y');
        }

        if ($question->role?->value === 'cpf') {
            return self::maskCpf($answer);
        }

        if ($question->role?->value === 'tel') {
            return self::maskTelefone($answer);
        }

        return $answer;
    }

    public static function maskCpf(?string $cpf): string
    {
        $digits = preg_replace('/\D/', '', $cpf ?? '');

        if (strlen($digits) !== 11) {
            return $cpf ?? '';
        }

        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits);
    }

    public static function maskTelefone(?string $telefone): string
    {
        $digits = preg_replace('/\D/', '', $telefone ?? '');

        if (strlen($digits) === 11) {
            return '('.substr($digits, 0, 2).') '.substr($digits, 2, 5).'-'.substr($digits, 7);
        }

        if (strlen($digits) === 10) {
            return '('.substr($digits, 0, 2).') '.substr($digits, 2, 4).'-'.substr($digits, 6);
        }

        return $telefone ?? '';
    }
}
