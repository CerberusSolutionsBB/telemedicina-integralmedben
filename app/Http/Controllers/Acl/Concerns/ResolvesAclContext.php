<?php

namespace App\Http\Controllers\Acl\Concerns;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * O módulo ACL é o mesmo para o central e para os tenants: cada contexto
 * gerencia os usuários, perfis e permissões do seu próprio banco.
 */
trait ResolvesAclContext
{
    public const ADMIN_ROLE = 'Admin';

    public const GUARD = 'web';

    protected function aclRoutePrefix(): string
    {
        return tenant() ? 'tenant.acl.' : 'acl.';
    }

    protected function aclRedirect(string $name, array $params = []): RedirectResponse
    {
        return redirect()->route($this->aclRoutePrefix().$name, $params);
    }

    protected function aclRender(string $component, array $props = []): Response
    {
        return Inertia::render("ACL/{$component}", [
            ...$props,
            'acl' => $this->aclContext(),
        ]);
    }

    private function aclContext(): array
    {
        $tenant = tenant();
        $user = request()->user();

        $can = [];
        foreach (['users', 'roles', 'permissions'] as $resource) {
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                $can["{$resource}.{$action}"] = (bool) $user?->can("acl.{$resource}.{$action}");
            }
        }

        return [
            'context' => $tenant ? 'tenant' : 'central',
            'routePrefix' => $this->aclRoutePrefix(),
            'tenantName' => $tenant?->name,
            'tenantPhoto' => $tenant?->photo_url,
            'can' => $can,
        ];
    }
}
