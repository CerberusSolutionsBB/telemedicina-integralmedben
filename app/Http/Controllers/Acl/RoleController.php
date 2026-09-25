<?php

namespace App\Http\Controllers\Acl;

use App\Http\Controllers\Acl\Concerns\ResolvesAclContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Acl\RoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use ResolvesAclContext;

    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $roles = Role::query()
            ->where('guard_name', self::GUARD)
            ->withCount(['users', 'permissions'])
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get(['id', 'name', 'created_at'])
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users_count,
                'permissions_count' => $role->permissions_count,
                'protected' => $role->name === self::ADMIN_ROLE,
            ]);

        return $this->aclRender('Roles/Index', [
            'roles' => $roles,
            'totalPermissions' => Permission::where('guard_name', self::GUARD)->count(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return $this->aclRender('Roles/Create', [
            'permissions' => $this->permissionOptions(),
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            Role::create(['name' => $request->input('name'), 'guard_name' => self::GUARD])
                ->syncPermissions($request->input('permissions', []));
        });

        return $this->aclRedirect('roles.index')->with('success', "Perfil {$request->input('name')} criado com sucesso.");
    }

    public function edit(Role $role): Response
    {
        $role->load('permissions:id,name')->loadCount('users');

        return $this->aclRender('Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
                'users_count' => $role->users_count,
                'protected' => $role->name === self::ADMIN_ROLE,
            ],
            'permissions' => $this->permissionOptions(),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        // O perfil Admin não pode ser renomeado e sempre possui todas as permissões
        if ($role->name === self::ADMIN_ROLE) {
            $role->syncPermissions(Permission::where('guard_name', self::GUARD)->get());

            return $this->aclRedirect('roles.index')->with('success', 'O perfil Admin mantém acesso total.');
        }

        DB::transaction(function () use ($request, $role) {
            $role->update(['name' => $request->input('name')]);
            $role->syncPermissions($request->input('permissions', []));
        });

        return $this->aclRedirect('roles.index')->with('success', "Perfil {$role->name} atualizado com sucesso.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === self::ADMIN_ROLE) {
            return back()->with('error', 'O perfil Admin não pode ser excluído.');
        }

        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return back()->with('error', "Este perfil está atribuído a {$usersCount} usuário(s). Remova-o dos usuários antes de excluir.");
        }

        $role->delete();

        return $this->aclRedirect('roles.index')->with('success', "Perfil {$role->name} excluído com sucesso.");
    }

    private function permissionOptions()
    {
        return Permission::query()
            ->where('guard_name', self::GUARD)
            ->orderBy('name')
            ->pluck('name');
    }
}
