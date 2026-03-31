<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            $role = Role::firstOrCreate(
                ['name' => 'Admin', 'guard_name' => 'web']
            );
            $role->syncPermissions(Permission::all());
            $user->assignRole($role);
            event(new Registered($user));
            Auth::login($user);
            if ($user->hasRole('Admin')) {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('dashboard');
        } catch (Exception $e) {
            Log::error('Erro no registro: '.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Erro ao criar conta. Tente novamente.']);
        }
    }
}
