<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Aliases dos middlewares do Spatie Permission
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Middlewares web (Inertia + Assets)
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Grupo tenant (Tenancy)
        $middleware->appendToGroup('tenant', [
            InitializeTenancyByDomain::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Módulo ACL: em requisições Inertia, erros HTTP viram mensagem amigável
        // (toast) em vez do modal com o HTML da página de erro
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (! $request->header('X-Inertia') || ! $request->routeIs('acl.*', 'tenant.acl.*')) {
                return $response;
            }

            $status = $response->getStatusCode();
            $messages = [
                403 => 'Você não tem permissão para realizar esta ação.',
                404 => 'O registro não foi encontrado. Ele pode ter sido excluído.',
                419 => 'Sua sessão expirou. Recarregue a página e tente novamente.',
                429 => 'Muitas tentativas em pouco tempo. Aguarde um instante e tente novamente.',
                500 => 'Ocorreu um erro inesperado. Tente novamente ou contate o suporte.',
                503 => 'O sistema está temporariamente indisponível. Tente novamente em instantes.',
            ];

            // Em desenvolvimento mantém a página de debug dos erros 5xx
            if (! isset($messages[$status]) || ($status >= 500 && config('app.debug'))) {
                return $response;
            }

            return back()->with('error', $messages[$status]);
        });
    })->create();
