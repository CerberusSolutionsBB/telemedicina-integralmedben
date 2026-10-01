<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Perfis de usuário específicos dos tenants. Criados sem permissões: o que cada
 * perfil pode fazer é definido no Controle de Acesso do tenant.
 *
 * Idempotente e só age em contexto de tenant (no central não faz nada).
 *   php artisan tenants:seed --class=TenantRolesSeeder
 */
class TenantRolesSeeder extends Seeder
{
    public const ROLES = [
        'Beneficiário',
        'Atendente',
        'Vendedor',
    ];

    public function run(): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        foreach (self::ROLES as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
