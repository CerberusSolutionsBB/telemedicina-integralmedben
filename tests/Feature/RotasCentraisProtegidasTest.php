<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Garante que nenhuma rota do painel central fique aberta sem login
 * ou sem permissão (antes, a página do parceiro expunha CPFs sem login).
 */
class RotasCentraisProtegidasTest extends TestCase
{
    /**
     * Rotas públicas de propósito.
     */
    private const PUBLICAS = [
        'forms.public.show',
        'forms.public.store',
        'forms.public.thanks',
        // Prévia do cupom no formulário público (com throttle).
        'forms.public.cupom',
        // Logo e cartão aparecem na página pública do parceiro e no painel do tenant.
        'pagina.configuracao.logo.show',
        'pagina.configuracao.cartao-dinamico.imagem.show',
    ];

    /**
     * Módulos administrativos: além de login, exigem permissão.
     */
    private const MODULOS_COM_PERMISSAO = [
        'pagina.',
        'leis.',
        'configuracoes.',
        'financeiro.',
        'tenants.',
        'central-users.',
        'sms-templates.',
        'forms.sms-templates.',
    ];

    public function test_rotas_centrais_exigem_login(): void
    {
        $abertas = $this->rotasCentrais()
            ->reject(fn (Route $route) => $this->exigeLogin($route))
            ->map(fn (Route $route) => implode('|', $route->methods()).' '.$route->uri())
            ->values()
            ->all();

        $this->assertSame([], $abertas, 'Rotas centrais sem o middleware auth.');
    }

    public function test_modulos_administrativos_exigem_permissao(): void
    {
        $semPermissao = $this->rotasCentrais()
            ->filter(fn (Route $route) => Str::startsWith((string) $route->getName(), self::MODULOS_COM_PERMISSAO))
            ->reject(fn (Route $route) => collect($route->gatherMiddleware())
                ->contains(fn ($middleware) => is_string($middleware) && Str::startsWith($middleware, 'permission:')))
            ->map(fn (Route $route) => $route->getName())
            ->values()
            ->all();

        $this->assertSame([], $semPermissao, 'Rotas administrativas sem permission:.');
    }

    /**
     * Rotas da aplicação no domínio central, fora as públicas de propósito
     * e as de visitante (login, cadastro, senha).
     */
    private function rotasCentrais()
    {
        return collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route) => Str::startsWith($route->getActionName(), 'App\\'))
            ->reject(fn (Route $route) => in_array($route->getName(), self::PUBLICAS, true))
            ->reject(function (Route $route) {
                $middleware = collect($route->gatherMiddleware())->filter(fn ($m) => is_string($m));

                // Rotas de tenant têm autenticação própria; as de visitante são públicas.
                return $middleware->contains(fn ($m) => Str::contains($m, ['PreventAccessFromCentralDomains', 'InitializeTenancy']) || $m === 'tenant')
                    || $middleware->contains(fn ($m) => Str::startsWith($m, 'guest'));
            });
    }

    private function exigeLogin(Route $route): bool
    {
        $excluidos = $route->excludedMiddleware();

        return collect($route->gatherMiddleware())
            ->contains(fn ($m) => is_string($m) && ($m === 'auth' || Str::startsWith($m, 'auth:')) && ! in_array($m, $excluidos, true));
    }
}
