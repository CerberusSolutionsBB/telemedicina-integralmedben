<?php

namespace App\Support;

/**
 * Catálogo de planos no formato de opções para o front:
 * - planos SIPROV (config('siprov.planos')): integram com a SIPROV/telemedicina;
 * - planos internos (config('planos.internos')): próprios do sistema, sem integração.
 */
class Planos
{
    /**
     * @return array<int, array{value: string, label: string, siprov: bool}>
     */
    public static function options(): array
    {
        $siprov = collect(config('siprov.planos', []))
            ->map(fn ($codigo, $key) => [
                'value' => (string) $codigo,
                'label' => self::labelSiprov($key),
                'siprov' => true,
            ]);

        $internos = collect(config('planos.internos', []))
            ->map(fn ($label, $codigo) => [
                'value' => (string) $codigo,
                'label' => $label,
                'siprov' => false,
            ]);

        return $siprov->values()->concat($internos->values())->all();
    }

    /**
     * @return array<int, string>
     */
    public static function codigos(): array
    {
        return array_column(self::options(), 'value');
    }

    /**
     * Plano registrado na SIPROV (associado + benefício)? Internos não são.
     */
    public static function integraSiprov(?string $codigo): bool
    {
        return in_array((string) $codigo, array_map('strval', array_values(config('siprov.planos', []))), true);
    }

    /**
     * Plano familiar? Nele o beneficiário informa os membros da família.
     */
    public static function familiar(?string $codigo): bool
    {
        return (string) $codigo !== '' && (string) $codigo === (string) config('siprov.planos.clinica_familiar');
    }

    private static function labelSiprov(string $key): string
    {
        return match ($key) {
            'clinica_familiar' => 'Clínica Familiar',
            'clinica_individual' => 'Clínica Individual',
            'saude_mental' => 'Saúde Mental',
            default => ucfirst(str_replace('_', ' ', $key)),
        };
    }
}
