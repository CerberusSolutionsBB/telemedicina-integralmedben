<?php
namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantAdminUserService
{
    public function execute(array $data, Tenant $tenant): User
    {
        tenancy()->initialize($tenant);

        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $roleAdmin = Role::firstOrCreate([
                'name'       => 'Admin',
                'guard_name' => 'web',
            ]);

            $user = User::create([
                'name'     => $data['nome'],
                'email'    => $data['email'],
                'password' => Hash::make($data['senha']),
            ]);

            $user->assignRole($roleAdmin);

            return $user;
        } finally {
            tenancy()->end();
        }
    }
}
