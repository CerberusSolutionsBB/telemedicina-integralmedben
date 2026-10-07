<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Models\TenantQuantidadeParceiro;
use App\Models\User;
use App\Services\Tenant\TenantPlanoCotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginaPlanoTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Sem eventos para não provisionar o banco do tenant.
        $this->tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-planos']));
        $this->user = User::factory()->create();
    }

    public function test_sincroniza_planos_e_quantidades(): void
    {
        $this->plano('331386', 5);

        $response = $this->actingAs($this->user)->put(route('pagina.configuracao.planos', $this->tenant->id), [
            'planos' => [
                ['cod_plano' => '331385', 'quantidade' => 10],
                ['cod_plano' => '331384', 'quantidade' => 3],
            ],
        ]);

        $response->assertRedirect(route('pagina.show', $this->tenant->id));

        $planos = TenantPlano::where('tenant_id', $this->tenant->id)->pluck('quantidade', 'cod_plano')->all();

        $this->assertSame(['331384' => 3, '331385' => 10], collect($planos)->sortKeys()->all());
    }

    public function test_salva_valor_de_cada_plano(): void
    {
        $this->plano('331385', 2);

        $this->actingAs($this->user)
            ->put(route('pagina.configuracao.planos', $this->tenant->id), [
                'planos' => [
                    ['cod_plano' => '331385', 'quantidade' => 2, 'valor' => 49.9],
                    ['cod_plano' => '331384', 'quantidade' => 1, 'valor' => null],
                ],
            ])
            ->assertRedirect(route('pagina.show', $this->tenant->id));

        $valores = TenantPlano::where('tenant_id', $this->tenant->id)->pluck('valor', 'cod_plano')->all();

        $this->assertSame('49.90', $valores['331385']);
        $this->assertNull($valores['331384']);
    }

    public function test_rejeita_valor_negativo(): void
    {
        $this->actingAs($this->user)
            ->put(route('pagina.configuracao.planos', $this->tenant->id), [
                'planos' => [['cod_plano' => '331385', 'quantidade' => 1, 'valor' => -1]],
            ])
            ->assertSessionHasErrors(['planos.0.valor']);
    }

    public function test_lista_vazia_remove_todos_os_planos(): void
    {
        $this->plano('331385', 2);

        $this->actingAs($this->user)
            ->put(route('pagina.configuracao.planos', $this->tenant->id), ['planos' => []])
            ->assertRedirect(route('pagina.show', $this->tenant->id));

        $this->assertSame(0, TenantPlano::where('tenant_id', $this->tenant->id)->count());
    }

    public function test_rejeita_plano_invalido_e_quantidade_zero(): void
    {
        $this->actingAs($this->user)
            ->put(route('pagina.configuracao.planos', $this->tenant->id), [
                'planos' => [
                    ['cod_plano' => '999999', 'quantidade' => 1],
                    ['cod_plano' => '331385', 'quantidade' => 0],
                ],
            ])
            ->assertSessionHasErrors(['planos.0.cod_plano', 'planos.1.quantidade']);

        $this->assertSame(0, TenantPlano::where('tenant_id', $this->tenant->id)->count());
    }

    public function test_exige_autenticacao(): void
    {
        $this->put(route('pagina.configuracao.planos', $this->tenant->id), ['planos' => []])
            ->assertRedirect(route('login'));
    }

    public function test_nao_permite_quantidade_abaixo_do_uso_nem_remover_plano_em_uso(): void
    {
        $this->plano('331385', 5);
        $this->plano('331384', 5);
        $this->vincular(['331385', '331384']);
        $this->vincular(['331385']);

        // 331385 tem 2 em uso; 331384 tem 1 em uso e está sendo removido.
        $this->actingAs($this->user)
            ->put(route('pagina.configuracao.planos', $this->tenant->id), [
                'planos' => [['cod_plano' => '331385', 'quantidade' => 1]],
            ])
            ->assertSessionHasErrors('planos');

        $this->assertSame(5, TenantPlano::where('cod_plano', '331385')->value('quantidade'));
        $this->assertSame(2, TenantPlano::where('tenant_id', $this->tenant->id)->count());

        $this->actingAs($this->user)
            ->put(route('pagina.configuracao.planos', $this->tenant->id), [
                'planos' => [
                    ['cod_plano' => '331385', 'quantidade' => 2],
                    ['cod_plano' => '331384', 'quantidade' => 1],
                ],
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_vinculo_siprov_respeita_cota_de_todos_os_planos_do_associado(): void
    {
        // Vínculo antigo (só cod_plano), anterior à cota: entra no saldo inicial.
        TelemedicinaTenant::create(['tenant_id' => $this->tenant->id, 'data' => ['siprov_id' => 1, 'cod_plano' => 331385]]);

        $this->plano('331385', 2);
        $this->plano('331384', 1);
        $this->assertSame(1, TenantPlano::where('cod_plano', '331385')->value('saldo'));

        // Associado com os dois planos: cabe (331385: 1+1 <= 2, 331384: 0+1 <= 1).
        $this->putSiprov([$this->associado(10, [331385, 331384])])->assertSessionHasNoErrors();

        $vinculo = TelemedicinaTenant::where('data->siprov_id', 10)->first();
        $this->assertSame(['331385', '331384'], $vinculo->data['cod_planos']);

        // Um registro por plano no histórico de Planos.
        $registros = Audit::where('event', 'registro_plano')->where('auditable_id', $vinculo->id)->get();
        $this->assertEqualsCanonicalizing(['331385', '331384'], $registros->pluck('new_values.cod_plano')->all());
        $this->assertSame(['vinculo_siprov'], $registros->pluck('new_values.origem')->unique()->values()->all());

        // Ambos os planos esgotados agora.
        $this->putSiprov([$this->associado(11, [331384])])->assertSessionHasErrors('siprov_items');
        $this->putSiprov([$this->associado(12, [331385])])->assertSessionHasErrors('siprov_items');

        $this->assertSame(2, TelemedicinaTenant::where('tenant_id', $this->tenant->id)->count());
    }

    public function test_vinculo_siprov_bloqueia_plano_nao_habilitado_e_lote_inteiro(): void
    {
        $this->plano('331385', 10);

        $this->putSiprov([
            $this->associado(20, [331385]),
            $this->associado(21, [331386]),
        ])->assertSessionHasErrors('siprov_items');

        $this->putSiprov([$this->associado(22, [])])->assertSessionHasErrors('siprov_items');

        $this->assertSame(0, TelemedicinaTenant::where('tenant_id', $this->tenant->id)->count());
    }

    public function test_zerar_contagem_devolve_saldo_mantem_vinculos_e_audita(): void
    {
        config(['audit.console' => true]);
        $this->plano('331385', 5);
        $this->vincular(['331385']);
        $this->vincular(['331385']);
        $this->assertSame(3, TenantPlano::where('cod_plano', '331385')->value('saldo'));

        $this->actingAs($this->user)
            ->put(route('pagina.configuracao.planos.zerar', [$this->tenant->id, '331385']))
            ->assertRedirect(route('pagina.show', $this->tenant->id));

        $plano = TenantPlano::where('cod_plano', '331385')->sole();
        $this->assertSame(5, $plano->saldo);
        $this->assertSame(2, TelemedicinaTenant::where('tenant_id', $this->tenant->id)->count());

        $zeragem = TenantQuantidadeParceiro::where('tipo', TenantQuantidadeParceiro::TIPO_ZERAGEM)->sole();
        $this->assertSame(2, $zeragem->variacao);
        $this->assertSame(5, $zeragem->quantidade);

        $this->assertTrue(Audit::where('auditable_type', TenantPlano::class)->where('auditable_id', $plano->id)
            ->where('event', 'updated')->get()->contains(fn ($a) => $a->new_values === ['saldo' => 5]));

        // Desvincular depois de zerar não passa o saldo do contratado.
        app(TenantPlanoCotaService::class)->devolver(TelemedicinaTenant::first());
        $this->assertSame(5, TenantPlano::where('cod_plano', '331385')->value('saldo'));
    }

    public function test_zerar_contagem_exige_login_e_plano_habilitado(): void
    {
        $this->put(route('pagina.configuracao.planos.zerar', [$this->tenant->id, '331385']))
            ->assertRedirect(route('login'));

        $this->actingAs($this->user)
            ->put(route('pagina.configuracao.planos.zerar', [$this->tenant->id, '331385']))
            ->assertSessionHasErrors('plano');
    }

    public function test_sessao_expirada_em_put_inertia_redireciona_para_login_com_303(): void
    {
        $origem = route('pagina.show', $this->tenant->id);

        $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1', 'Referer' => $origem])
            ->from($origem)
            ->put(route('pagina.configuracao.planos.zerar', [$this->tenant->id, '331385']))
            ->assertStatus(303)
            ->assertRedirect(route('login'));

        // Depois do login, volta para a página do parceiro (e não para a URL do PUT).
        $this->assertSame($origem, session('url.intended'));
    }

    public function test_configura_cota_do_plano_interno(): void
    {
        $this->actingAs($this->user)
            ->put(route('pagina.configuracao.planos', $this->tenant->id), [
                'planos' => [['cod_plano' => 'beneficios', 'quantidade' => 30]],
            ])
            ->assertSessionHasNoErrors();

        $plano = TenantPlano::where('tenant_id', $this->tenant->id)->where('cod_plano', 'beneficios')->sole();
        $this->assertSame([30, 30], [$plano->quantidade, $plano->saldo]);
    }

    private function vincular(array $codPlanos): void
    {
        $vinculo = TelemedicinaTenant::create([
            'tenant_id' => $this->tenant->id,
            'data' => ['siprov_id' => random_int(1, 99999), 'cod_plano' => $codPlanos[0], 'cod_planos' => $codPlanos],
        ]);

        app(TenantPlanoCotaService::class)->consumir($this->tenant->id, $codPlanos, null, $vinculo->id);
    }

    private function associado(int $codPessoa, array $codPlanos): array
    {
        return [
            'codPessoa' => $codPessoa,
            'nomePessoa' => "Associado {$codPessoa}",
            'cpfCnpj' => '',
            'codBeneficio' => $codPessoa,
            'planos' => array_map(fn ($c) => ['codPlano' => $c, 'nome' => "Plano {$c}"], $codPlanos),
        ];
    }

    private function putSiprov(array $itens)
    {
        return $this->actingAs($this->user)->put(route('pagina.configuracao.telemedicina', $this->tenant->id), [
            'enabled' => true,
            'siprov_items' => $itens,
        ]);
    }

    public function test_cards_da_listagem_ignoram_contratos_de_parceiro_excluido(): void
    {
        $this->plano('331384', 20);

        $excluido = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-excluido']));
        TenantPlano::create(['tenant_id' => $excluido->id, 'cod_plano' => '331384', 'quantidade' => 30, 'saldo' => 30]);
        Tenant::withoutEvents(fn () => $excluido->delete());

        $this->actingAs($this->user)
            ->get(route('pagina.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('totais.contratos', 1)
                ->where('totais.planos.0.value', '331384')
                ->where('totais.planos.0.parceiros', 1)
                ->where('totais.planos.0.vagas', 20));
    }

    private function plano(string $codPlano, int $quantidade): void
    {
        app(TenantPlanoCotaService::class)->ajustarQuantidade($this->tenant->id, $codPlano, $quantidade);
    }
}
