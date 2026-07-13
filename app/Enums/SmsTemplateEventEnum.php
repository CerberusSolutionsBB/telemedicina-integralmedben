<?php

namespace App\Enums;

enum SmsTemplateEventEnum: string
{
    case PatientCreated = 'patient.created';
    case SiprovIntegrated = 'siprov.integrated';

    public function label(): string
    {
        return match ($this) {
            self::PatientCreated => 'Novo Paciente Cadastrado',
            self::SiprovIntegrated => 'Telemedicina Integrada',
        };
    }
}
