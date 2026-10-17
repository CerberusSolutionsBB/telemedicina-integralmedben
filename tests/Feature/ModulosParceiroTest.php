<?php

namespace Tests\Feature;

use App\Http\Middleware\ModuloHabilitado;
use App\Models\Tenant;
use App\Models\TenantsDetail;
use App\Models\User;
use App\Support\ModulosParceiro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ModulosParceiroTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Sem eventos para não provisionar o banco do tenant.
        $this->tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'modulostenant']));
        TenantsDetail::create(['tenant_id' => $this->tenant->id, 'configuracao' => ['cartao_paciente_enabled' => true]]);
    }

    public function test_modulos_comecam_desligados_e_ligam_um_a_um(): void
    {
        $this->assertSame(['servicos' => false, 'convenios' => false, 'cupons_beneficio' => false], ModulosParceiro::doTenant($this->tenant->id));

        ModulosParceiro::definir($this->tenant->id, 'convenios', true);

        $this->assertTrue(ModulosParceiro::habilitado($this->tenant->id, 'convenios'));
        $this->assertFalse(ModulosParceiro::habilitado($this->tenant->id, 'servicos'));
        // As outras configurações do parceiro continuam lá.
        $this->assertTrue(TenantsDetail::where('tenant_id', $this->tenant->id)->value('configuracao')['cartao_paciente_enabled']);
    }

    public function test_admin_liga_e_desliga_pela_pagina_do_parceiro(): void
    {
        $rota = fn (string $modulo) => route('pagina.configuracao.modulos', [$this->tenant->id, $modulo]);

        $this->actingAs(User::factory()->create())->put($rota('servicos'), ['habilitado' => true])->assertForbidden();

        Permission::firstOrCreate(['name' => 'paginas.edit', 'guard_name' => 'web']);
        $admin = tap(User::factory()->create())->givePermissionTo('paginas.edit');

        $this->actingAs($admin)->put($rota('servicos'), ['habilitado' => true])
            ->assertRedirect(route('pagina.show', $this->tenant->id))
            ->assertSessionHas('success', 'Serviços habilitado para este parceiro.');
        $this->assertTrue(ModulosParceiro::habilitado($this->tenant->id, 'servicos'));

        $this->actingAs($admin)->put($rota('servicos'), ['habilitado' => false]);
        $this->assertFalse(ModulosParceiro::habilitado($this->tenant->id, 'servicos'));

        $this->actingAs($admin)->put($rota('modulo-que-nao-existe'), ['habilitado' => true])->assertSessionHasErrors('modulo');
    }

    public function test_middleware_bloqueia_modulo_desligado(): void
    {
        $resposta = (new ModuloHabilitado)->handle(Request::create('/servicos'), fn () => response('liberado'), 'servicos');

        $this->assertTrue($resposta->isRedirect());
        $this->assertStringContainsString('não está habilitado', session('error'));
    }
}
