<?php

namespace App\Support;

/**
 * Planos SIPROV disponíveis (config('siprov.planos')) no formato de opções para o front.
 */
class SiprovPlanos
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(config('siprov.planos', []))
            ->map(fn ($codigo, $key) => [
                'value' => (string) $codigo,
                'label' => self::label($key),
            ])
            ->values()
            ->toArray();
    }

    /**
     * @return array<int, string>
     */
    public static function codigos(): array
    {
        return array_map('strval', array_values(config('siprov.planos', [])));
    }

    private static function label(string $key): string
    {
        return match ($key) {
            'clinica_familiar' => 'Clínica Familiar',
            'clinica_individual' => 'Clínica Individual',
            'saude_mental' => 'Saúde Mental',
            default => ucfirst(str_replace('_', ' ', $key)),
        };
    }
}
