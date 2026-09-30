<?php

namespace Tests\Feature;

use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Models\TenantQuantidadeParceiro;
use App\Services\Tenant\TenantPlanoCotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

class TenantPlanoSaldoTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private TenantPlanoCotaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-saldo']));
        $this->service = app(TenantPlanoCotaService::class);
    }

    public function test_consumo_decrementa_saldo_e_registra_extrato_com_paciente(): void
    {
        $this->service->ajustarQuantidade($this->tenant->id, '331385', 2);
        $vinculo = $this->vinculo(['331385']);

        $this->service->consumir($this->tenant->id, ['331385'], 42, $vinculo->id);

        $this->assertSame(1, $this->saldo('331385'));

        $movimento = TenantQuantidadeParceiro::where('tipo', TenantQuantidadeParceiro::TIPO_CONSUMO)->sole();
        $this->assertSame($this->tenant->id, $movimento->tenant_id);
        $this->assertSame(42, $movimento->parceiro_id);
        $this->assertSame(-1, $movimento->variacao);
        $this->assertSame(1, $movimento->quantidade);
        $this->assertSame($vinculo->id, $movimento->telemedicina_tenant_id);
    }

    public function test_sem_saldo_nao_consome_nada(): void
    {
        $this->service->ajustarQuantidade($this->tenant->id, '331385', 1);
        $this->service->ajustarQuantidade($this->tenant->id, '331384', 0 + 1);
        $this->service->consumir($this->tenant->id, ['331384'], 1);

        try {
            // 331385 tem vaga, 331384 não: nenhum dos dois é consumido.
            $this->service->consumir($this->tenant->id, ['331385', '331384'], 2);
            $this->fail('ValidationException esperada.');
        } catch (ValidationException) {
        }

        $this->assertSame(1, $this->saldo('331385'));
        $this->assertSame(0, $this->saldo('331384'));
    }

    public function test_devolucao_ao_desvincular_e_ajuste_de_quantidade(): void
    {
        $this->service->ajustarQuantidade($this->tenant->id, '331385', 3);
        $vinculo = $this->vinculo(['331385']);
        $this->service->consumir($this->tenant->id, ['331385'], 42, $vinculo->id);

        $this->service->devolver($vinculo, 42);
        $this->assertSame(3, $this->saldo('331385'));

        // Ajuste leva o saldo junto (+2) e fica no extrato.
        $this->service->ajustarQuantidade($this->tenant->id, '331385', 5);
        $this->assertSame(5, $this->saldo('331385'));

        $this->assertSame(
            ['ajuste', 'consumo', 'devolucao', 'ajuste'],
            TenantQuantidadeParceiro::orderBy('id')->pluck('tipo')->all(),
        );
    }

    public function test_alteracoes_de_saldo_e_vinculos_sao_auditadas(): void
    {
        // Por padrão o pacote não audita no console (onde os testes rodam).
        config(['audit.console' => true]);

        $this->service->ajustarQuantidade($this->tenant->id, '331385', 2);
        $vinculo = $this->vinculo(['331385']);
        $this->service->consumir($this->tenant->id, ['331385'], 42, $vinculo->id);

        $plano = TenantPlano::where('cod_plano', '331385')->sole();

        $atualizacao = Audit::where('auditable_type', TenantPlano::class)
            ->where('auditable_id', $plano->id)
            ->where('event', 'updated')
            ->sole();
        $this->assertSame(['saldo' => 2], $atualizacao->old_values);
        $this->assertSame(['saldo' => 1], $atualizacao->new_values);

        $this->assertTrue(Audit::where('auditable_type', TelemedicinaTenant::class)
            ->where('auditable_id', $vinculo->id)->where('event', 'created')->exists());
    }

    private function vinculo(array $codPlanos): TelemedicinaTenant
    {
        return TelemedicinaTenant::create([
            'tenant_id' => $this->tenant->id,
            'data' => ['siprov_id' => random_int(1, 99999), 'cod_plano' => $codPlanos[0], 'cod_planos' => $codPlanos],
        ]);
    }

    private function saldo(string $codPlano): int
    {
        return TenantPlano::where('tenant_id', $this->tenant->id)->where('cod_plano', $codPlano)->value('saldo');
    }
}
