<?php

namespace Tests\Unit;

use App\Audit\DispositivoResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DispositivoResolverTest extends TestCase
{
    #[DataProvider('userAgents')]
    public function test_identifica_tipo_sistema_e_navegador(string $userAgent, array $esperado): void
    {
        $this->assertSame($esperado, DispositivoResolver::analisar($userAgent));
    }

    public static function userAgents(): array
    {
        return [
            'Chrome no Windows' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                ['tipo' => 'computador', 'sistema' => 'Windows', 'navegador' => 'Chrome'],
            ],
            'Edge no Windows' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0',
                ['tipo' => 'computador', 'sistema' => 'Windows', 'navegador' => 'Edge'],
            ],
            'Chrome no Android (celular)' => [
                'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36',
                ['tipo' => 'celular', 'sistema' => 'Android', 'navegador' => 'Chrome'],
            ],
            'Tablet Android' => [
                'Mozilla/5.0 (Linux; Android 13; SM-X700) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                ['tipo' => 'tablet', 'sistema' => 'Android', 'navegador' => 'Chrome'],
            ],
            'Safari no iPad' => [
                'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
                ['tipo' => 'tablet', 'sistema' => 'iOS', 'navegador' => 'Safari'],
            ],
            'Firefox no Linux' => [
                'Mozilla/5.0 (X11; Linux x86_64; rv:127.0) Gecko/20100101 Firefox/127.0',
                ['tipo' => 'computador', 'sistema' => 'Linux', 'navegador' => 'Firefox'],
            ],
            'Safari no macOS' => [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
                ['tipo' => 'computador', 'sistema' => 'macOS', 'navegador' => 'Safari'],
            ],
        ];
    }
}
