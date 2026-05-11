<?php
namespace App\Services\Auth;

use Illuminate\Support\Facades\Auth;

abstract class BaseAuthContextService
{
    protected function guestContext(string $type): array
    {
        return [
            'user'  => null,
            'can'   => [],
            'check' => false,
            'type'  => $type,
        ];
    }

    protected function logoutInvalidSession(): void
    {
        if (! Auth::check()) {
            return;
        }

        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    protected function userContext($user): array
    {
        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'avatar'            => $user->avatar ?? null,
            'created_at'        => $user->created_at?->format('d/m/Y H:i'),

            'roles'             => $user->getRoleNames()->toArray(),
            'permissions'       => $user->getPermissionNames()->toArray(),

            'is_admin'          => $user->hasRole('Admin'),
            'is_manager'        => $user->hasRole('Manager'),
            'is_editor'         => $user->hasRole('Editor'),
        ];
    }

    protected function permissions($user): array
    {
        return [
            'users'   => [
                'view'   => $user->can('users.view'),
                'create' => $user->can('users.create'),
                'edit'   => $user->can('users.edit'),
                'delete' => $user->can('users.delete'),
                'manage' => $user->can('users.manage'),
            ],

            'forms'   => [
                'view'              => $user->can('forms.view'),
                'create'            => $user->can('forms.create'),
                'edit'              => $user->can('forms.edit'),
                'delete'            => $user->can('forms.delete'),
                'update_status'     => $user->can('forms.update.status'),
                'toggle_visibility' => $user->can('forms.toggle.visibility'),
                'manage_all'        => $user->can('forms.manage.all'),
            ],

            'paginas' => [
                'view'   => $user->can('paginas.view'),
                'create' => $user->can('paginas.create'),
                'edit'   => $user->can('paginas.edit'),
                'delete' => $user->can('paginas.delete'),
                'show'   => $user->can('paginas.show'),
                'manage' => $user->can('paginas.manage'),
            ],

            'leis'    => [
                'view'   => $user->can('leis.view'),
                'create' => $user->can('leis.create'),
                'edit'   => $user->can('leis.edit'),
                'delete' => $user->can('leis.delete'),
            ],

            'manage'  => $user->hasAnyRole(['Admin', 'Manager']),
        ];
    }
}
