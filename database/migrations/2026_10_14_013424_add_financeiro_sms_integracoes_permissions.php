<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissões das rotas centrais que eram abertas a qualquer usuário logado.
 * Só acrescenta: não altera as permissões já ajustadas pela tela de ACL.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<int, string>> permissão => perfis que a recebem
     */
    private const PERMISSOES = [
        'financeiro.view' => ['Admin'],
        'sms-templates.manage' => ['Admin', 'Manager'],
        'integracoes.manage' => ['Admin'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSOES as $nome => $perfis) {
            $permissao = Permission::firstOrCreate(['name' => $nome, 'guard_name' => 'web']);

            Role::where('guard_name', 'web')->whereIn('name', $perfis)->get()
                ->each(fn (Role $role) => $role->givePermissionTo($permissao));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('guard_name', 'web')->whereIn('name', array_keys(self::PERMISSOES))->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
