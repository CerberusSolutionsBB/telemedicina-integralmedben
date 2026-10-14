<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AcessoPorPermissaoTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Sem eventos para não provisionar o banco do tenant.
        $this->tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-acesso']));
    }

    public function test_visitante_nao_ve_a_pagina_do_parceiro(): void
    {
        $this->get(route('pagina.show', $this->tenant->id))->assertRedirect(route('login'));
    }

    public function test_visitante_nao_exclui_tenant(): void
    {
        $this->delete(route('tenants.destroy'), ['id' => $this->tenant->id])->assertRedirect(route('login'));

        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
    }

    public function test_logo_do_parceiro_continua_publico(): void
    {
        $response = $this->get(route('pagina.configuracao.logo.show', $this->tenant->id));

        $this->assertFalse($response->isRedirect(route('login')));
    }

    public function test_usuario_sem_permissao_recebe_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('pagina.show', $this->tenant->id))->assertForbidden();
        $this->actingAs($user)->get(route('financeiro.index'))->assertForbidden();
        $this->actingAs($user)->delete(route('pagina.destroy', $this->tenant->id))->assertForbidden();

        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
    }

    public function test_permissao_libera_o_acesso(): void
    {
        $user = $this->usuarioCom(['paginas.view', 'financeiro.view']);

        $this->actingAs($user)->get(route('pagina.index'))->assertOk();
        $this->actingAs($user)->get(route('financeiro.index'))->assertOk();
    }

    /**
     * @param  array<int, string>  $permissoes
     */
    private function usuarioCom(array $permissoes): User
    {
        foreach ($permissoes as $permissao) {
            Permission::firstOrCreate(['name' => $permissao, 'guard_name' => 'web']);
        }

        return tap(User::factory()->create())->givePermissionTo($permissoes);
    }
}
