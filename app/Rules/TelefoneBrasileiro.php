<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Telefone brasileiro com DDD, somente números.
 *  - 11 dígitos: celular, começa com 9 após o DDD  → (86)99431-3116
 *  - 10 dígitos: fixo, começa com 2 a 5 após o DDD → (86)3222-1234
 */
class TelefoneBrasileiro implements ValidationRule
{
    // DDDs válidos no Brasil (Anatel)
    public const DDDS = [
        11, 12, 13, 14, 15, 16, 17, 18, 19,
        21, 22, 24, 27, 28,
        31, 32, 33, 34, 35, 37, 38,
        41, 42, 43, 44, 45, 46, 47, 48, 49,
        51, 53, 54, 55,
        61, 62, 63, 64, 65, 66, 67, 68, 69,
        71, 73, 74, 75, 77, 79,
        81, 82, 83, 84, 85, 86, 87, 88, 89,
        91, 92, 93, 94, 95, 96, 97, 98, 99,
    ];

    public function __construct(private ?string $label = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $campo = $this->label ? "O campo \"{$this->label}\"" : 'O telefone';
        $numero = preg_replace('/\D/', '', (string) $value);

        if (! preg_match('/^\d{10,11}$/', $numero)) {
            $fail("{$campo} deve ter DDD + número, ex.: (86)99431-3116.");

            return;
        }

        if (! in_array((int) substr($numero, 0, 2), self::DDDS, true)) {
            $fail("{$campo} tem um DDD inválido (".substr($numero, 0, 2).').');

            return;
        }

        $assinante = substr($numero, 2);

        if (strlen($numero) === 11 && $assinante[0] !== '9') {
            $fail("{$campo} não é um celular válido: após o DDD o número deve começar com 9.");

            return;
        }

        if (strlen($numero) === 10 && ! in_array($assinante[0], ['2', '3', '4', '5'], true)) {
            $fail("{$campo} não é válido: celulares precisam do 9 na frente, ex.: (86)99431-3116.");

            return;
        }

        // Números de teste como (86)99999-9999 ou (86)91111-1111
        if (preg_match('/^(\d)\1+$/', substr($assinante, 1))) {
            $fail("{$campo} parece não ser um número real.");
        }
    }
}
