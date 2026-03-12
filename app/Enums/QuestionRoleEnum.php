<?php

namespace App\Enums;

enum QuestionRoleEnum: string
{
    // Identificação — perguntas obrigatórias de sistema
    case Nome      = 'nome';
    case Email     = 'email';
    case Cpf       = 'cpf';
    case Tel       = 'tel';
    case BirthDate = 'birth_date';
    case City      = 'city';

    // Plano — pergunta opcional, só vinculada em tenants com telemedicina
    case Plan = 'plan';
}
