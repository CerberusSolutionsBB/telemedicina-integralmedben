<?php

namespace App\Enums;

enum SmsTemplateEventEnum: string
{
    case PatientCreated = 'patient.created';

    public function label(): string
    {
        return match ($this) {
            self::PatientCreated => 'Novo Paciente Cadastrado',
        };
    }
}
