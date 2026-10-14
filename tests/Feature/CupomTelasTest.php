<?php

namespace Tests\Feature;

use App\Models\Cupom;
use App\Models\CupomUso;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Models\TenantsDetail;
use App\Models\User;
use App\Services\Siprov\SiprovIntegrationService;
use App\Services\Tenant\TenantPlanoCotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CupomTelasTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Sem eventos para não provisionar o banco do tenant.
        $this->tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'cupomtenant']));
        TenantsDetail::create(['tenant_id' => $this->tenant->id]);
    }

    public function test_admin_cria_cupom_e_quem_nao_tem_permissao_recebe_403(): void
    {
        $this->actingAs(User::factory()->create())->post(route('financeiro.cupons.store'), $this->dados())->assertForbidden();

        $this->actingAs($this->usuarioCom(['cupons.manage']))
            ->post(route('financeiro.cupons.store'), $this->dados(['codigo' => 'natal@25', 'tenant_id' => $this->tenant->id]))
            ->assertSessionHasErrors('codigo');

        $this->actingAs($this->usuarioCom(['cupons.manage']))
            ->post(route('financeiro.cupons.store'), $this->dados(['codigo' => 'natal25', 'tenant_id' => $this->tenant->id]))
            ->assertRedirect(route('financeiro.cupons.show', Cupom::sole()));

        $cupom = Cupom::sole();
        $this->assertSame(['NATAL25', 'cupomtenant'], [$cupom->codigo, $cupom->tenant_id]);
    }

    public function test_telas_centrais_abrem(): void
    {
        $user = $this->usuarioCom(['cupons.manage']);
        $cupom = Cupom::create([...$this->dados(['codigo' => 'TELA']), 'inicio_em' => now()->subDay(), 'expira_em' => now()->addDay()]);

        $this->actingAs($user)->get(route('financeiro.cupons.index'))
            ->assertInertia(fn ($page) => $page->component('Financeiro/Cupons/Index')->where('cupons.0.situacao', 'ativo'));
        $this->actingAs($user)->get(route('financeiro.cupons.create'))
            ->assertInertia(fn ($page) => $page->component('Financeiro/Cupons/Create'));
        $this->actingAs($user)->get(route('financeiro.cupons.edit', $cupom))
            ->assertInertia(fn ($page) => $page->component('Financeiro/Cupons/Edit')->where('cupom.codigo', 'TELA'));
        $this->actingAs($user)->get(route('financeiro.cupons.show', $cupom))
            ->assertInertia(fn ($page) => $page->component('Financeiro/Cupons/Show')
                ->where('cupom.codigo', 'TELA')
                ->where('cupom.pode_excluir', true)
                ->where('cupom.aplicavel_label', 'Todos os planos')
                ->has('usos', 0));
    }

    public function test_exclui_cupom_sem_uso_e_mantem_o_ja_usado(): void
    {
        $user = $this->usuarioCom(['cupons.manage']);
        $livre = Cupom::create([...$this->dados(['codigo' => 'LIVRE']), 'inicio_em' => now()->subDay(), 'expira_em' => now()->addDay()]);
        $usado = Cupom::create([...$this->dados(['codigo' => 'JAUSADO']), 'inicio_em' => now()->subDay(), 'expira_em' => now()->addDay()]);
        CupomUso::create([
            'cupom_id' => $usado->id, 'tenant_id' => $this->tenant->id, 'cpf' => '11111111111', 'cod_plano' => '331385',
            'origem_type' => TelemedicinaTenant::class, 'origem_id' => 1, 'valor_original' => 100, 'desconto' => 10,
            'valor_final' => 90, 'estornado_em' => now(),
        ]);

        $this->actingAs($user)->delete(route('financeiro.cupons.destroy', $livre))->assertRedirect(route('financeiro.cupons.index'));
        $this->actingAs($user)->delete(route('financeiro.cupons.destroy', $usado))->assertSessionHas('error');

        $this->assertSame(['JAUSADO'], Cupom::pluck('codigo')->all());
    }

    public function test_gera_o_pdf_do_cupom(): void
    {
        $cupom = Cupom::create([...$this->dados(['codigo' => 'PDF10']), 'inicio_em' => now()->subDay(), 'expira_em' => now()->addDay()]);

        $this->actingAs(User::factory()->create())->get(route('financeiro.cupons.pdf', $cupom))->assertForbidden();

        $this->actingAs($this->usuarioCom(['cupons.manage']))->get(route('financeiro.cupons.pdf', $cupom))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_cupom_usado_nao_muda_valor_mas_pode_ser_pausado(): void
    {
        $user = $this->usuarioCom(['cupons.manage']);
        $cupom = Cupom::create([...$this->dados(['codigo' => 'USADO']), 'inicio_em' => now()->subDay(), 'expira_em' => now()->addDay(), 'usos' => 1]);

        $this->actingAs($user)->put(route('financeiro.cupons.update', $cupom), $this->dados(['codigo' => 'USADO', 'valor' => 50]))
            ->assertSessionHasErrors('valor');

        $this->actingAs($user)->put(route('financeiro.cupons.status', $cupom), ['status' => 'pausado'])->assertRedirect();

        $this->assertSame(['10.00', 'pausado'], [$cupom->fresh()->valor, $cupom->fresh()->status]);

        // Cancelado é final: não volta a ativo.
        $this->actingAs($user)->put(route('financeiro.cupons.status', $cupom), ['status' => 'cancelado']);
        $this->actingAs($user)->put(route('financeiro.cupons.status', $cupom), ['status' => 'ativo'])->assertSessionHas('error');
        $this->assertSame('cancelado', $cupom->fresh()->status);
    }

    public function test_admin_define_e_remove_o_teto_do_parceiro(): void
    {
        $user = $this->usuarioCom(['paginas.edit']);

        $this->actingAs($user)->put(route('pagina.configuracao.cupom-teto', $this->tenant->id), ['teto' => 15])->assertRedirect();
        $this->assertEquals(15, TenantsDetail::where('tenant_id', $this->tenant->id)->value('configuracao')['cupom_desconto_maximo']);

        $this->actingAs($user)->put(route('pagina.configuracao.cupom-teto', $this->tenant->id), ['teto' => null])->assertRedirect();
        $this->assertNull(TenantsDetail::where('tenant_id', $this->tenant->id)->value('configuracao')['cupom_desconto_maximo']);
    }

    public function test_formulario_publico_mostra_previa_e_aplica_o_cupom(): void
    {
        [$form, $campoNome, $campoCpf] = $this->formularioComPlano();
        Cupom::create([...$this->dados(['codigo' => 'FORM20', 'valor' => 20]), 'inicio_em' => now()->subDay(), 'expira_em' => now()->addDay()]);

        $this->postJson("http://cupomtenant.localhost/forms/f/{$form->slug}/cupom", ['cupom' => 'form20', 'cpf' => '12345678909'])
            ->assertOk()
            ->assertJson(['valido' => true, 'desconto' => 20, 'valor_final' => 80]);

        $this->postJson("http://cupomtenant.localhost/forms/f/{$form->slug}/cupom", ['cupom' => 'NAOEXISTE'])
            ->assertStatus(422)
            ->assertJson(['valido' => false]);

        $this->mock(SiprovIntegrationService::class, fn (MockInterface $mock) => $mock->shouldReceive('execute')->once()
            ->andReturn(['associado' => ['codPessoa' => 9], 'beneficio' => ['codBeneficio' => 8]]));

        $this->post("http://cupomtenant.localhost/forms/f/{$form->slug}", [
            'answers' => [$campoNome => 'Fulano', $campoCpf => '12345678909'],
            'cupom' => 'FORM20',
        ])->assertRedirect(route('forms.public.thanks', $form->slug));

        $uso = CupomUso::sole();
        $this->assertSame([TelemedicinaTenant::class, '80.00'], [$uso->origem_type, $uso->valor_final]);
    }

    public function test_cupom_invalido_bloqueia_a_resposta_do_formulario(): void
    {
        [$form, $campoNome, $campoCpf] = $this->formularioComPlano();

        $this->post("http://cupomtenant.localhost/forms/f/{$form->slug}", [
            'answers' => [$campoNome => 'Fulano', $campoCpf => '12345678909'],
            'cupom' => 'NAOEXISTE',
        ])->assertSessionHasErrors('cupom');

        $this->assertSame(0, FormResponse::count());
    }

    /**
     * @return array{0: Form, 1: int, 2: int}
     */
    private function formularioComPlano(): array
    {
        app(TenantPlanoCotaService::class)->ajustarQuantidade($this->tenant->id, '331385', 3);
        TenantPlano::where('cod_plano', '331385')->update(['valor' => 100]);

        $form = Form::factory()->create([
            'status' => 'ativo', 'is_public' => true, 'expires_at' => null, 'response_limit' => null,
            'status_beneficio' => true, 'plano_id' => '331385',
        ]);

        return [
            $form,
            $form->fields()->create(['type' => 'text', 'label' => 'Nome completo', 'required' => true, 'order' => 1])->id,
            $form->fields()->create(['type' => 'cpf', 'label' => 'CPF', 'required' => true, 'order' => 2])->id,
        ];
    }

    private function dados(array $extra = []): array
    {
        return [
            'codigo' => 'PROMO',
            'tipo' => Cupom::TIPO_PERCENTUAL,
            'valor' => 10,
            'origem' => 'manual',
            'inicio_em' => now()->format('Y-m-d\TH:i'),
            'expira_em' => now()->addMonth()->format('Y-m-d\TH:i'),
            'limite_uso_por_cpf' => 1,
            'aplicavel_a' => Cupom::APLICAVEL_TODOS,
            ...$extra,
        ];
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
