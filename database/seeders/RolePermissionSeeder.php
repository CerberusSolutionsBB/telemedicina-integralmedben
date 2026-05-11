<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = $this->permissions();

        $this->createPermissions($permissions);
        $this->createRoles();
        $this->createUsers();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('✅ Roles, permissões e usuários criados com sucesso!');
        $this->command->line('   - Admin: admin@admin.com / password');
        $this->command->line('   - Manager: manager@localhost / password');
        $this->command->line('   - Editor: editor@localhost / password');
        $this->command->line('   - User: user@localhost / password');
    }

    private function permissions(): array
    {
        return [
            'users'   => [
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
                'users.manage',
            ],

            'forms'   => [
                'forms.view',
                'forms.create',
                'forms.edit',
                'forms.delete',
                'forms.update.status',
                'forms.toggle.visibility',
                'forms.manage.all',
            ],

            'paginas' => [
                'paginas.view',
                'paginas.create',
                'paginas.edit',
                'paginas.delete',
                'paginas.show',
                'paginas.manage',
            ],

            'leis'    => [
                'leis.view',
                'leis.create',
                'leis.edit',
                'leis.delete',
            ],
        ];
    }

    private function createPermissions(array $groups): void
    {
        foreach ($groups as $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate([
                    'name'       => $permission,
                    'guard_name' => 'web',
                ]);
            }
        }
    }

    private function createRoles(): void
    {
        Role::firstOrCreate([
            'name'       => 'Admin',
            'guard_name' => 'web',
        ])->syncPermissions(Permission::all());

        Role::firstOrCreate([
            'name'       => 'Manager',
            'guard_name' => 'web',
        ])->syncPermissions([
            'users.view',
            'users.create',
            'users.edit',

            'forms.view',
            'forms.create',
            'forms.edit',
            'forms.delete',
            'forms.update.status',
            'forms.toggle.visibility',

            'paginas.view',
            'paginas.create',
            'paginas.edit',
            'paginas.delete',
            'paginas.show',

            'leis.view',
            'leis.create',
            'leis.edit',
            'leis.delete',
        ]);

        Role::firstOrCreate([
            'name'       => 'Editor',
            'guard_name' => 'web',
        ])->syncPermissions([
            'forms.view',
            'forms.create',
            'forms.edit',
            'forms.update.status',
            'forms.toggle.visibility',

            'paginas.view',
            'paginas.show',

            'leis.view',
            'leis.create',
            'leis.edit',
        ]);

        Role::firstOrCreate([
            'name'       => 'User',
            'guard_name' => 'web',
        ])->syncPermissions([
            'forms.view',
            'forms.create',
            'forms.toggle.visibility',

            'paginas.view',
            'paginas.show',

            'leis.view',
        ]);
    }

    private function createUsers(): void
    {
        $users = [
            [
                'name'  => 'Administrador',
                'email' => 'admin@admin.com',
                'role'  => 'Admin',
            ],
            [
                'name'  => 'Gerente',
                'email' => 'manager@localhost',
                'role'  => 'Manager',
            ],
            [
                'name'  => 'Editor',
                'email' => 'editor@localhost',
                'role'  => 'Editor',
            ],
            [
                'name'  => 'Usuário',
                'email' => 'user@localhost',
                'role'  => 'User',
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$data['role']]);
        }
    }
}
