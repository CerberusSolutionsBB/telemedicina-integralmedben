<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Services\User\UserService;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $users = $this->userService->getUsers($search);
        $tenant = Tenant::find(tenant('id'));

        return Inertia::render('User/Index', [
            'users' => $users,
            'filters' => ['search' => $search],
            'tenantName' => $tenant->name,
            'tenantPhoto' => $tenant->photo_url,
        ]);
    }

    public function create()
    {
        $tenant = Tenant::find(tenant('id'));

        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('User/Create', [
            'roles' => $roles,
            'tenantName' => $tenant->name,
            'tenantPhoto' => $tenant->photo_url,
        ]);
    }

    public function edit(User $user)
    {
        $tenant = Tenant::find(tenant('id'));

        $user->load('roles');

        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('User/Edit', [
            'user' => $user,
            'roles' => $roles,
            'tenantName' => $tenant->name,
            'tenantPhoto' => $tenant->photo_url,
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->create($request->validated());

        if ($request->filled('role')) {
            $user->assignRole($request->input('role'));
        }

        return redirect()->route('users.index')->with('success', 'Usuário criado com sucesso.');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->update($user, $request->validated());

        if ($request->filled('role')) {
            $user->syncRoles([$request->input('role')]);
        }

        return redirect()->route('users.index')->with('success', 'Usuário atualizado com sucesso.');
    }

    public function destroy(User $user)
    {
        $this->userService->delete($user);

        return redirect()->route('users.index')->with('success', 'Usuário excluído com sucesso.');
    }
}
