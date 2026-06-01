<?php

namespace App\Enums;

enum EnvironmentEnum: string
{
    case LOCAL = 'local';
    case DEVELOPMENT = 'development';
    case HOMOLOGATION = 'homologation';
    case PRODUCTION = 'production';

    /**
     * Retorna os domínios centrais do ambiente
     */
    public function domains(): array
    {
        return match ($this) {
            self::LOCAL => [
                'localhost',
                '127.0.0.1',
            ],
            self::DEVELOPMENT => [
                'dev.telemedicinamedben.com.br',
            ],
            self::HOMOLOGATION => [
                'hmg.telemedicinamedben.com.br',
            ],
            self::PRODUCTION => [
                'telemedicinamedben.com.br',
                'integralmedben.com.br',
            ],
        };
    }

    /**
     * Retorna o ambiente atual baseado no APP_ENV
     */
    public static function current(): self
    {
        $env = env('APP_ENV', 'local');

        return self::tryFrom($env) ?? self::LOCAL;
    }

    /**
     * Retorna todos os domínios centrais de todos os ambientes
     */
    public static function allDomains(): array
    {
        return array_merge(
            ...array_map(
                fn (self $env) => $env->domains(),
                self::cases()
            )
        );
    }

    /**
     * Retorna os domínios do ambiente atual
     */
    public static function currentDomains(): array
    {
        return self::current()->domains();
    }
}
