<?php

namespace App\Audit;

use Illuminate\Support\Facades\Request;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\Resolver;

/**
 * Tipo de dispositivo, sistema e navegador a partir do user agent da requisição.
 */
class DispositivoResolver implements Resolver
{
    public static function resolve(Auditable $auditable): ?array
    {
        $userAgent = (string) ($auditable->preloadedResolverData['user_agent'] ?? Request::header('User-Agent', ''));

        return $userAgent === '' ? null : self::analisar($userAgent);
    }

    /**
     * @return array{tipo: string, sistema: string, navegador: string}
     */
    public static function analisar(string $userAgent): array
    {
        return [
            'tipo' => self::tipo($userAgent),
            'sistema' => self::primeiro($userAgent, [
                '/iPhone|iPad|iPod/i' => 'iOS',
                '/Android/i' => 'Android',
                '/CrOS/i' => 'ChromeOS',
                '/Windows/i' => 'Windows',
                '/Mac OS X|Macintosh/i' => 'macOS',
                '/Linux/i' => 'Linux',
            ]),
            // Ordem importa: Edge/Opera/Samsung também se anunciam como Chrome e Safari.
            'navegador' => self::primeiro($userAgent, [
                '/Edg\//i' => 'Edge',
                '/OPR\/|Opera/i' => 'Opera',
                '/SamsungBrowser/i' => 'Samsung Internet',
                '/Firefox|FxiOS/i' => 'Firefox',
                '/Chrome|CriOS/i' => 'Chrome',
                '/Safari/i' => 'Safari',
            ]),
        ];
    }

    private static function tipo(string $userAgent): string
    {
        if (preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $userAgent)) {
            return 'tablet';
        }

        return preg_match('/Mobi|iPhone|iPod|Android/i', $userAgent) ? 'celular' : 'computador';
    }

    private static function primeiro(string $userAgent, array $padroes): string
    {
        foreach ($padroes as $regex => $nome) {
            if (preg_match($regex, $userAgent)) {
                return $nome;
            }
        }

        return 'Desconhecido';
    }
}
