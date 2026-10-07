<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Acesso padrão dos perfis de tenant: define o que Atendente, Vendedor e
 * Beneficiário podem acessar e garante o Admin com acesso total (inclusive
 * Controle de Acesso).
 *
 * Atenção: sobrescreve as permissões atuais desses perfis (syncPermissions).
 * Idempotente e só age em contexto de tenant.
 *   php artisan tenants:seed --class=TenantRolePermissionsSeeder --tenants=med_bem
 */
class TenantRolePermissionsSeeder extends Seeder
{
    /** Permissões padrão de cada perfil de usuário do tenant. */
    public const MATRIZ = [
        'Atendente' => [
            'pacientes.view',
            'pacientes.create',
            'pacientes.edit',
            'pacientes.show',
            'forms.view',
            'siprov.view',
            'siprov.show',
        ],
        'Vendedor' => [
            'pacientes.view',
            'pacientes.show',
            'forms.view',
        ],
        'Beneficiário' => [
            'pacientes.view',
            'pacientes.show',
        ],
    ];

    public const ADMIN_EMAIL = 'admintenant@admin.com';

    public function run(): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Permissões dos módulos: criadas apenas se o tenant ainda não tiver.
        if (Permission::query()->doesntExist()) {
            $this->call([RolePermissionSeeder::class, AclPermissionSeeder::class]);
        }

        foreach (self::MATRIZ as $perfil => $permissoes) {
            Role::firstOrCreate(['name' => $perfil, 'guard_name' => 'web'])
                ->syncPermissions($permissoes);
        }

        $this->adminComAcessoTotal();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function adminComAcessoTotal(): void
    {
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
            ->syncPermissions(Permission::where('guard_name', 'web')->get());

        $admin = User::firstOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => 'AdminTenant',
                'password' => Hash::make('admintenant'),
                'email_verified_at' => now(),
            ],
        );

        if (! $admin->email_verified_at) {
            $admin->forceFill(['email_verified_at' => now()])->save();
        }

        $admin->syncRoles(['Admin']);
    }
}
