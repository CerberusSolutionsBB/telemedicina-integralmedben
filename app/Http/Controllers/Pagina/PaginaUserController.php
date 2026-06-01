<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Stancl\Tenancy\Facades\Tenancy;

class PaginaUserController extends Controller
{
    public function index(Request $request, Tenant $tenant): Response
    {
        $tenantData = $this->tenantData($tenant);
        $search = $request->string('search')->toString();

        Tenancy::initialize($tenant);

        try {
            $users = User::query()
                ->with(['roles.permissions', 'permissions'])
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString()
                ->through(fn ($user) => $this->userPayload($user));
        } finally {
            Tenancy::end();
        }

        return Inertia::render('Pagina/Users/Index', [
            'tenant' => $tenantData,
            'filters' => ['search' => $search],
            'users' => $users,
        ]);
    }

    public function create(Tenant $tenant): Response
    {
        $tenantData = $this->tenantData($tenant);

        Tenancy::initialize($tenant);

        try {
            $roles = Role::query()
                ->orderBy('name')
                ->get(['id', 'name']);
        } finally {
            Tenancy::end();
        }

        return Inertia::render('Pagina/Users/Create', [
            'tenant' => $tenantData,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
        ]);

        Tenancy::initialize($tenant);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->syncRoles($validated['roles'] ?? []);
        } finally {
            Tenancy::end();
        }

        return redirect()
            ->route('pagina.users.index', $tenant->id)
            ->with('message', 'Usuário criado com sucesso!')
            ->with('type', 'success');
    }

    public function edit(Tenant $tenant, int $user): Response
    {
        $tenantData = $this->tenantData($tenant);

        Tenancy::initialize($tenant);

        try {
            $userModel = User::with('roles')->findOrFail($user);

            $roles = Role::query()
                ->orderBy('name')
                ->get(['id', 'name']);

            $selectedRoles = $userModel->roles
                ->pluck('name')
                ->values();
        } finally {
            Tenancy::end();
        }

        return Inertia::render('Pagina/Users/Edit', [
            'tenant' => $tenantData,
            'roles' => $roles,
            'selectedRoles' => $selectedRoles,
            'user' => [
                'id' => $userModel->id,
                'name' => $userModel->name,
                'email' => $userModel->email,
            ],
        ]);
    }

    public function update(Request $request, Tenant $tenant, $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string'],
        ]);

        Tenancy::initialize($tenant);

        try {
            $userModel = User::findOrFail($user);

            $data = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];

            if (! empty($validated['password'])) {
                $data['password'] = Hash::make($validated['password']);
            }

            $userModel->update($data);

            $userModel->syncRoles([$validated['role']]);

            return redirect()
                ->route('pagina.users.index', $tenant->id)
                ->with('message', 'Usuário atualizado com sucesso!')
                ->with('type', 'success');

        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors([
                    'general' => 'Erro ao atualizar usuário: '.$e->getMessage(),
                ])
                ->withInput();

        } finally {
            Tenancy::end();
        }
    }

    public function destroy(Tenant $tenant, int $user): RedirectResponse
    {
        Tenancy::initialize($tenant);

        try {
            $userModel = User::findOrFail($user);
            $userModel->delete();
        } finally {
            Tenancy::end();
        }

        return redirect()
            ->route('pagina.users.index', $tenant->id)
            ->with('message', 'Usuário removido com sucesso!')
            ->with('type', 'success');
    }

    private function tenantData(Tenant $tenant): array
    {
        $tenant->load('details');

        return [
            'id' => $tenant->id,
            'name' => $tenant->details->first()?->descricao ?? $tenant->name ?? $tenant->id,
        ];
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at?->format('d/m/Y H:i'),

            'roles' => $user->roles
                ->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->values(),
                ])
                ->values(),

            'direct_permissions' => $user->permissions
                ->pluck('name')
                ->values(),

            'all_permissions' => $user
                ->getAllPermissions()
                ->pluck('name')
                ->unique()
                ->values(),
        ];
    }
}
