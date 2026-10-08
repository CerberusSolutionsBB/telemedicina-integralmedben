<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * DESENVOLVIMENTO: 10 usuários fictícios com o perfil Vendedor no tenant.
 * Idempotente (busca pelo e-mail). Senha de todos: password.
 *   php artisan tenants:seed --class=VendedoresFakeSeeder --tenants=med_bem
 */
class VendedoresFakeSeeder extends Seeder
{
    public const QUANTIDADE = 10;

    public function run(): void
    {
        if (! tenancy()->initialized || app()->isProduction()) {
            return;
        }

        Role::firstOrCreate(['name' => 'Vendedor', 'guard_name' => 'web']);

        $faker = fake('pt_BR');
        $dominio = str_replace('_', '-', tenant('id')).'.test';

        foreach (range(1, self::QUANTIDADE) as $i) {
            $user = User::firstOrCreate(
                ['email' => sprintf('vendedor%02d@%s', $i, $dominio)],
                [
                    'name' => $faker->firstName().' '.$faker->lastName(),
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $user->assignRole('Vendedor');
        }
    }
}
