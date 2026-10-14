<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\Cupom;
use App\Models\CupomUso;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Models\TenantPlanoBeneficiario;
use App\Models\TenantsDetail;
use App\Services\Cupom\CupomService;
use App\Services\Tenant\PacientePlanoService;
use App\Services\Tenant\TenantPlanoCotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CupomVendaTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Sem eventos para não provisionar o banco do tenant.
        $this->tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-cupom']));
        TenantsDetail::create(['tenant_id' => $this->tenant->id]);

        app(TenantPlanoCotaService::class)->ajustarQuantidade($this->tenant->id, 'beneficios', 5);
        TenantPlano::where('cod_plano', 'beneficios')->update(['valor' => 100, 'comissao_tipo' => 'percentual', 'comissao_valor' => 10]);
    }

    public function test_venda_com_cupom_grava_desconto_e_comissao_sobre_o_valor_final(): void
    {
        $this->cupom(['codigo' => 'PROMO10', 'tipo' => 'percentual', 'valor' => 10]);

        $vinculo = $this->vender('11111111111', ' promo10 ');

        $uso = CupomUso::sole();
        $this->assertSame(['100.00', '10.00', '90.00'], [$uso->valor_original, $uso->desconto, $uso->valor_final]);
        $this->assertSame(1, Cupom::sole()->usos);

        $audit = Audit::where('auditable_id', $vinculo->id)->where('event', 'registro_plano')->sole();
        $this->assertSame('PROMO10', $audit->new_values['cupom']);
        $this->assertEquals(90, $audit->new_values['valor']);
        $this->assertEquals(9, $audit->new_values['comissao']);
    }

    public function test_cupom_invalido_nao_cria_vinculo_nem_consome_vaga(): void
    {
        $this->cupom(['codigo' => 'UNICO', 'limite_uso_total' => 1]);
        $this->vender('11111111111', 'UNICO');

        $this->assertCupomFalha(fn () => $this->vender('22222222222', 'UNICO'), 'limite de usos');

        $this->assertSame(1, TenantPlanoBeneficiario::count());
        $this->assertSame(4, TenantPlano::where('cod_plano', 'beneficios')->value('saldo'));
    }

    public function test_regras_de_validacao(): void
    {
        $service = app(CupomService::class);
        $outro = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'outro-tenant']));

        $this->cupom(['codigo' => 'OUTRO', 'tenant_id' => $outro->id]);
        $this->cupom(['codigo' => 'VENCIDO', 'inicio_em' => now()->subDays(10), 'expira_em' => now()->subDay()]);
        $this->cupom(['codigo' => 'PAUSADO', 'status' => Cupom::STATUS_PAUSADO]);
        $this->cupom(['codigo' => 'MINIMO', 'valor_minimo' => 150]);
        $this->cupom(['codigo' => 'SOPLANO', 'aplicavel_a' => Cupom::APLICAVEL_PLANOS])->planos()->create(['cod_plano' => '331385']);

        $casos = [
            'NAOEXISTE' => 'inválido',
            'OUTRO' => 'inválido',
            'VENCIDO' => 'expirou',
            'PAUSADO' => 'pausado',
            'MINIMO' => 'mínimo',
            'SOPLANO' => 'não vale para o plano',
        ];

        foreach ($casos as $codigo => $mensagem) {
            $this->assertCupomFalha(fn () => $service->validar($codigo, $this->tenant->id, 'beneficios', '11111111111'), $mensagem);
        }
    }

    public function test_limite_por_cpf_e_primeiro_contrato(): void
    {
        $this->cupom(['codigo' => 'CPF1']);
        $this->vender('11111111111', 'CPF1');

        $service = app(CupomService::class);
        $this->assertCupomFalha(fn () => $service->validar('CPF1', $this->tenant->id, 'beneficios', '111.111.111-11'), 'já usou');

        $this->cupom(['codigo' => 'PRIMEIRO', 'aplicavel_a' => Cupom::APLICAVEL_PRIMEIRO_CONTRATO]);
        $this->assertCupomFalha(fn () => $service->validar('PRIMEIRO', $this->tenant->id, 'beneficios', '11111111111'), 'primeiro contrato');
        $this->assertSame(10.0, $service->validar('PRIMEIRO', $this->tenant->id, 'beneficios', '33333333333')['desconto']);
    }

    public function test_cupom_do_parceiro_respeita_o_teto(): void
    {
        $service = app(CupomService::class);
        $this->cupom(['codigo' => 'PARCEIRO50', 'valor' => 50, 'tenant_id' => $this->tenant->id]);

        $this->assertCupomFalha(fn () => $service->validar('PARCEIRO50', $this->tenant->id, 'beneficios', '11111111111'), 'desativados');

        $service->definirTeto($this->tenant->id, 20);

        $this->assertSame(20.0, $service->validar('PARCEIRO50', $this->tenant->id, 'beneficios', '11111111111')['desconto']);
    }

    public function test_devolver_a_vaga_estorna_o_uso_do_cupom(): void
    {
        app(TenantPlanoCotaService::class)->ajustarQuantidade($this->tenant->id, '331385', 2);
        TenantPlano::where('cod_plano', '331385')->update(['valor' => 80]);
        $this->cupom(['codigo' => 'VOLTA', 'tipo' => Cupom::TIPO_VALOR_FIXO, 'valor' => 30, 'limite_uso_total' => 1]);

        $vinculo = app(PacientePlanoService::class)->registrarVinculo(
            $this->tenant->id, '331385', 'Fulano', '44444444444', ['associado' => ['codPessoa' => 9]], null, 'cadastro_paciente', 'VOLTA',
        );

        $this->assertSame('esgotado', Cupom::sole()->situacao());

        app(TenantPlanoCotaService::class)->devolver(TelemedicinaTenant::findOrFail($vinculo->id));

        $this->assertNotNull(CupomUso::sole()->estornado_em);
        $this->assertSame(Cupom::STATUS_ATIVO, Cupom::sole()->situacao());
    }

    private function vender(string $cpf, ?string $cupom): TenantPlanoBeneficiario
    {
        return app(PacientePlanoService::class)->registrarVinculoInterno($this->tenant->id, 'beneficios', 'Fulano', $cpf, null, 'cadastro_paciente', $cupom);
    }

    private function cupom(array $dados = []): Cupom
    {
        return Cupom::create([
            'codigo' => 'CUPOM',
            'tipo' => Cupom::TIPO_PERCENTUAL,
            'valor' => 10,
            'inicio_em' => now()->subDay(),
            'expira_em' => now()->addMonth(),
            ...$dados,
        ]);
    }

    private function assertCupomFalha(callable $acao, string $trecho): void
    {
        try {
            $acao();
        } catch (ValidationException $e) {
            $this->assertStringContainsString($trecho, $e->errors()['cupom'][0] ?? '');

            return;
        }

        $this->fail("Esperava falha do cupom contendo \"{$trecho}\".");
    }
}
