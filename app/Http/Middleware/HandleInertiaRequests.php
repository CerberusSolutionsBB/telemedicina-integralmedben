<?php

namespace App\Http\Middleware;

use App\Services\Cupom\CupomService;
use App\Support\BeneficiarioPermissoes;
use App\Support\ModulosParceiro;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar_url,
                    'roles' => $user->getRoleNames(),
                    // Permissões efetivas: diretas + herdadas dos perfis
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'is_admin' => $user->hasRole('Admin'),
                ] : null,
            ],
            // Ações do CRUD de beneficiários habilitadas para o parceiro (só no tenant).
            'beneficiarioPermissoes' => fn () => tenancy()->initialized
                ? BeneficiarioPermissoes::doTenant(tenant('id'))
                : null,
            // Módulos opcionais ligados para o parceiro (só no tenant): o menu esconde os desligados.
            'modulosParceiro' => fn () => tenancy()->initialized
                ? ModulosParceiro::doTenant(tenant('id'))
                : null,
            // Teto de desconto (%) dos cupons do parceiro; null = cupons desativados para ele.
            'cupomTeto' => fn () => tenancy()->initialized
                ? app(CupomService::class)->tetoDoTenant((string) tenant('id'))
                : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
