<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissões do módulo ACL (Usuários, Perfis e Permissões).
 *
 * Idempotente: pode ser executado no central e em todos os tenants.
 *   php artisan db:seed --class=AclPermissionSeeder
 *   php artisan tenants:seed --class=AclPermissionSeeder
 */
class AclPermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'acl.users.view',
        'acl.users.create',
        'acl.users.edit',
        'acl.users.delete',
        'acl.roles.view',
        'acl.roles.create',
        'acl.roles.edit',
        'acl.roles.delete',
        'acl.permissions.view',
        'acl.permissions.create',
        'acl.permissions.edit',
        'acl.permissions.delete',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // O perfil Admin sempre tem acesso total
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
            ->syncPermissions(Permission::where('guard_name', 'web')->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
