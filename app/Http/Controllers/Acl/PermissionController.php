<?php

namespace App\Http\Controllers\Acl;

use App\Http\Controllers\Acl\Concerns\ResolvesAclContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Acl\PermissionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    use ResolvesAclContext;

    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $permissions = Permission::query()
            ->where('guard_name', self::GUARD)
            ->withCount(['roles', 'users'])
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Permission $permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'roles_count' => $permission->roles_count,
                'users_count' => $permission->users_count,
                'protected' => $this->isProtected($permission),
            ]);

        return $this->aclRender('Permissions/Index', [
            'permissions' => $permissions,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(PermissionRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $permission = Permission::create(['name' => $request->input('name'), 'guard_name' => self::GUARD]);

            // O perfil Admin sempre recebe as novas permissões
            Role::where('name', self::ADMIN_ROLE)->where('guard_name', self::GUARD)->first()?->givePermissionTo($permission);
        });

        return $this->aclRedirect('permissions.index')->with('success', "Permissão {$request->input('name')} criada com sucesso.");
    }

    public function update(PermissionRequest $request, Permission $permission): RedirectResponse
    {
        if ($this->isProtected($permission)) {
            return back()->with('error', 'Permissões do módulo de controle de acesso não podem ser alteradas.');
        }

        $permission->update(['name' => $request->input('name')]);

        return $this->aclRedirect('permissions.index')->with('success', "Permissão {$permission->name} atualizada com sucesso.");
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        if ($this->isProtected($permission)) {
            return back()->with('error', 'Permissões do módulo de controle de acesso não podem ser excluídas.');
        }

        $permission->delete();

        return $this->aclRedirect('permissions.index')->with('success', "Permissão {$permission->name} excluída com sucesso.");
    }

    // Sem as permissões acl.* ninguém conseguiria administrar o próprio ACL
    private function isProtected(Permission $permission): bool
    {
        return Str::startsWith($permission->name, 'acl.');
    }
}
