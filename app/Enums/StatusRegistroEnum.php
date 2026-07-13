<?php

namespace App\Enums;

enum StatusRegistroEnum: string
{
    case Formulario = 'formulario';
    case FormDinamico = 'form-dinamico';
    case Importacao = 'importacao';
    case FormPublico = 'form-publico';

    public function label(): string
    {
        return match ($this) {
            self::Formulario => 'Formulario',
            self::FormDinamico => 'Formulario Dinamico',
            self::Importacao => 'Importacao',
            self::FormPublico => 'Formulario Publico',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Formulario => 'bg-blue-100 text-blue-800',
            self::FormDinamico => 'bg-purple-100 text-purple-800',
            self::Importacao => 'bg-orange-100 text-orange-800',
            self::FormPublico => 'bg-teal-100 text-teal-800',
        };
    }
}
