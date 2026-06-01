<?php

namespace App\Exceptions;

use Exception;

class SiprovException extends Exception
{
    public static function authFailed(?string $message = null): self
    {
        return new self($message ?? 'Falha ao autenticar na SIPROV.');
    }

    public static function associadoFailed(?string $message = null): self
    {
        return new self($message ?? 'Falha ao cadastrar associado na SIPROV.');
    }

    public static function beneficioFailed(?string $message = null): self
    {
        return new self($message ?? 'Falha ao cadastrar benefício na SIPROV.');
    }
}
