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
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $userPermissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.manage',
        ];
        foreach ($userPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }
        $formPermissions = [
            'forms.view',
            'forms.create',
            'forms.edit',
            'forms.delete',
            'forms.update.status',
            'forms.toggle.visibility',
            'forms.manage.all',
        ];
        foreach ($formPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin'],
            ['guard_name' => 'web']
        );
        $adminRole->syncPermissions(Permission::all());
        $managerRole = Role::firstOrCreate(
            ['name' => 'Manager'],
            ['guard_name' => 'web']
        );
        $managerRole->syncPermissions([
            'users.view', 'users.create', 'users.edit',
            'forms.view', 'forms.create', 'forms.edit', 'forms.delete',
            'forms.update.status', 'forms.toggle.visibility',
        ]);
        $editorRole = Role::firstOrCreate(
            ['name' => 'Editor'],
            ['guard_name' => 'web']
        );
        $editorRole->syncPermissions([
            'forms.view', 'forms.create', 'forms.edit',
            'forms.update.status', 'forms.toggle.visibility',
        ]);
        $userRole = Role::firstOrCreate(
            ['name' => 'User'],
            ['guard_name' => 'web']
        );
        $userRole->syncPermissions([
            'forms.view', 'forms.create',
            'forms.toggle.visibility',
        ]);
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name'              => 'Administrador',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['Admin']);
        $manager = User::firstOrCreate(
            ['email' => 'manager@localhost'],
            [
                'name'              => 'Gerente',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $manager->syncRoles(['Manager']);
        $editor = User::firstOrCreate(
            ['email' => 'editor@localhost'],
            [
                'name'              => 'Editor',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $editor->syncRoles(['Editor']);
        $user = User::firstOrCreate(
            ['email' => 'user@localhost'],
            [
                'name'              => 'Usuário',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->syncRoles(['User']);
        echo "Roles, permissões e usuários criados com sucesso!\n";
    }
}