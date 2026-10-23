<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantsDetail;
use App\Models\User;
use App\Support\BeneficiarioPermissoes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BeneficiarioPermissoesTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Sem eventos para não provisionar o banco do tenant.
        $this->tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'permissoestenant']));
        TenantsDetail::create(['tenant_id' => $this->tenant->id, 'configuracao' => ['cartao_paciente_enabled' => true]]);

        Permission::firstOrCreate(['name' => 'paginas.edit', 'guard_name' => 'web']);
        $this->admin = tap(User::factory()->create())->givePermissionTo('paginas.edit');
    }

    private function rota(): string
    {
        return route('pagina.configuracao.beneficiario', $this->tenant->id);
    }

    public function test_admin_salva_as_quatro_acoes_e_o_parceiro_le_o_que_foi_salvo(): void
    {
        $this->assertSame(BeneficiarioPermissoes::padrao(), BeneficiarioPermissoes::doTenant($this->tenant->id));

        $this->actingAs($this->admin)
            ->put($this->rota(), ['create' => true, 'edit' => false, 'delete' => true, 'status' => true])
            ->assertRedirect(route('pagina.show', $this->tenant->id))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ['create' => true, 'edit' => false, 'delete' => true, 'status' => true],
            BeneficiarioPermissoes::doTenant($this->tenant->id),
        );
        // As outras configurações do parceiro continuam lá.
        $this->assertTrue(TenantsDetail::where('tenant_id', $this->tenant->id)->value('configuracao')['cartao_paciente_enabled']);
    }

    public function test_acao_faltando_e_recusada_e_registrada_no_log(): void
    {
        Log::spy();

        // Era o que a tela mandava: "delete" sumia do envio.
        $this->actingAs($this->admin)
            ->put($this->rota(), ['create' => true, 'edit' => false, 'status' => false])
            ->assertSessionHasErrors('delete');

        $this->assertSame(BeneficiarioPermissoes::padrao(), BeneficiarioPermissoes::doTenant($this->tenant->id));
        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($mensagem, $contexto) => $mensagem === 'Permissões de beneficiário recusadas'
                && $contexto['tenant_id'] === $this->tenant->id
                && array_key_exists('delete', $contexto['erros']))
            ->once();
    }
}
