<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * DESENVOLVIMENTO: usuários fictícios para cada perfil do tenant, para testar a
 * seleção de participantes das metas. Idempotente (busca pelo e-mail).
 * Senha de todos: password.
 *   php artisan tenants:seed --class=UsuariosPorPerfilSeeder --tenants=med_bem
 */
class UsuariosPorPerfilSeeder extends Seeder
{
    /** Perfis que recebem usuários fictícios. */
    public const PERFIS = [
        'Admin',
        'Manager',
        'Editor',
        'User',
        'Beneficiário',
        'Atendente',
        'Vendedor',
    ];

    /** Usuários criados por perfil. */
    public const POR_PERFIL = 10;

    public function run(): void
    {
        if (! tenancy()->initialized || app()->isProduction()) {
            return;
        }

        $faker = fake('pt_BR');
        $dominio = str_replace('_', '-', tenant('id')).'.test';

        foreach (self::PERFIS as $nome) {
            $role = Role::firstOrCreate(['name' => $nome, 'guard_name' => 'web']);
            $slug = Str::slug($nome);

            foreach (range(1, self::POR_PERFIL) as $i) {
                $user = User::firstOrCreate(
                    ['email' => sprintf('%s%02d@%s', $slug, $i, $dominio)],
                    [
                        'name' => $faker->firstName().' '.$faker->lastName(),
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                    ],
                );

                // Garante o login mesmo em usuários criados antes (sem verificação).
                if (! $user->email_verified_at) {
                    $user->forceFill(['email_verified_at' => now()])->save();
                }

                $user->assignRole($role);
            }
        }
    }
}
