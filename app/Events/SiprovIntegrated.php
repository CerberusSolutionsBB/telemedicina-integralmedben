<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SiprovIntegrated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $siprovId,
        public ?string $tenantId,
        public string $nome,
        public string $cpf,
        public ?string $email,
        public ?string $sexo,
        public ?string $dataNascimento,
        public ?string $codPlano,
        public ?string $telefone,
    ) {}
}
