<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Throwable;

/**
 * Formatação pt-BR para exibição (telas e PDFs): máscaras de documentos e
 * telefones e datas no fuso de Brasília (o banco grava em UTC).
 */
class Formatar
{
    public const FUSO = 'America/Sao_Paulo';

    public static function digitos(?string $valor): string
    {
        return preg_replace('/\D/', '', (string) $valor);
    }

    /** 000.000.000-00 (valor original se não tiver 11 dígitos). */
    public static function cpf(?string $cpf): ?string
    {
        $digitos = self::digitos($cpf);

        return strlen($digitos) === 11
            ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digitos)
            : self::vazio($cpf);
    }

    /** (00) 00000-0000 ou (00) 0000-0000; aceita +55 na frente. */
    public static function telefone(?string $telefone): ?string
    {
        $digitos = self::digitos($telefone);

        if (strlen($digitos) > 11 && str_starts_with($digitos, '55')) {
            $digitos = substr($digitos, 2);
        }

        return match (strlen($digitos)) {
            11 => preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $digitos),
            10 => preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $digitos),
            default => self::vazio($telefone),
        };
    }

    /** 00000-000. */
    public static function cep(?string $cep): ?string
    {
        $digitos = self::digitos($cep);

        return strlen($digitos) === 8 ? substr($digitos, 0, 5).'-'.substr($digitos, 5) : self::vazio($cep);
    }

    /** dd/mm/aaaa (datas sem horário, como nascimento: sem conversão de fuso). */
    public static function data(mixed $valor): ?string
    {
        return self::carbon($valor)?->format('d/m/Y');
    }

    /** dd/mm/aaaa HH:mm no fuso de Brasília (valores do banco estão em UTC). */
    public static function dataHora(mixed $valor): ?string
    {
        return self::carbon($valor)?->setTimezone(self::FUSO)->format('d/m/Y H:i');
    }

    /** Data/hora digitada em Brasília (ex.: input datetime-local) convertida para UTC. */
    public static function localParaUtc(?string $valor): ?Carbon
    {
        if (! $valor) {
            return null;
        }

        try {
            return Carbon::parse($valor, self::FUSO)->utc();
        } catch (Throwable) {
            return null;
        }
    }

    public static function idade(mixed $nascimento): ?int
    {
        return self::carbon($nascimento)?->age;
    }

    public static function sexo(?string $sexo): ?string
    {
        return match (mb_strtolower(trim((string) $sexo))) {
            'm', 'masculino' => 'Masculino',
            'f', 'feminino' => 'Feminino',
            '' => null,
            default => $sexo,
        };
    }

    private static function carbon(mixed $valor): ?Carbon
    {
        if (! $valor) {
            return null;
        }

        try {
            return $valor instanceof \DateTimeInterface ? Carbon::instance($valor) : Carbon::parse($valor);
        } catch (Throwable) {
            return null;
        }
    }

    private static function vazio(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }
}
