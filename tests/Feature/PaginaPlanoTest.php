<?php

namespace Tests\Feature;

use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Services\Tenant\TenantPlanoCotaService;
use App\Models\User;
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

    private function plano(string $codPlano, int $quantidade): void
    {
        app(TenantPlanoCotaService::class)->ajustarQuantidade($this->tenant->id, $codPlano, $quantidade);
    }
}
