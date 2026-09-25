<?php

namespace App\Http\Controllers\Acl;

use App\Http\Controllers\Acl\Concerns\ResolvesAclContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Acl\AclUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use ResolvesAclContext;

    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());
        $role = $request->string('role')->toString();

        $users = User::query()
            ->with('roles:id,name')
            ->withCount('permissions')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($role !== '', fn ($q) => $q->role($role))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return $this->aclRender('Usuarios/Index', [
            'users' => $users,
            'roles' => $this->roleOptions(),
            'filters' => ['search' => $search, 'role' => $role],
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function create(): Response
    {
        return $this->aclRender('Usuarios/Create', [
            'roles' => $this->roleOptions(),
            'permissions' => $this->permissionOptions(),
        ]);
    }

    public function store(AclUserRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));
            $user->syncRoles($request->input('roles', []));
            $user->syncPermissions($request->input('permissions', []));

            return $user;
        });

        return $this->aclRedirect('users.index')->with('success', "Usuário {$user->name} criado com sucesso.");
    }

    public function edit(User $user): Response
    {
        $user->load('roles:id,name', 'permissions:id,name');

        return $this->aclRender('Usuarios/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->permissions->pluck('name'),
            ],
            'roles' => $this->roleOptions(),
            'permissions' => $this->permissionOptions(),
            'isCurrentUser' => $user->id === request()->user()->id,
        ]);
    }

    public function update(AclUserRequest $request, User $user): RedirectResponse
    {
        $roles = $request->input('roles', []);

        if ($user->hasRole(self::ADMIN_ROLE) && ! in_array(self::ADMIN_ROLE, $roles, true) && $this->isLastAdmin($user)) {
            throw ValidationException::withMessages([
                'roles' => 'Este é o último administrador. Defina outro usuário como Admin antes de remover este perfil.',
            ]);
        }

        DB::transaction(function () use ($request, $user, $roles) {
            $data = $request->safe()->only(['name', 'email']);
            if ($request->filled('password')) {
                $data['password'] = $request->input('password');
            }

            $user->update($data);
            $user->syncRoles($roles);
            $user->syncPermissions($request->input('permissions', []));
        });

        return $this->aclRedirect('users.index')->with('success', "Usuário {$user->name} atualizado com sucesso.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Você não pode excluir o seu próprio usuário.');
        }

        if ($user->hasRole(self::ADMIN_ROLE) && $this->isLastAdmin($user)) {
            return back()->with('error', 'Não é possível excluir o último administrador.');
        }

        $user->delete();

        return $this->aclRedirect('users.index')->with('success', "Usuário {$user->name} excluído com sucesso.");
    }

    private function isLastAdmin(User $user): bool
    {
        return User::role(self::ADMIN_ROLE)->whereKeyNot($user->getKey())->doesntExist();
    }

    private function roleOptions()
    {
        return Role::query()
            ->where('guard_name', self::GUARD)
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ]);
    }

    private function permissionOptions()
    {
        return Permission::query()
            ->where('guard_name', self::GUARD)
            ->orderBy('name')
            ->pluck('name');
    }
}
