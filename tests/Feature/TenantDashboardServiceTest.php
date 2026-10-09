<?php

namespace Tests\Feature;

use App\Enums\SmsStatusEnum;
use App\Http\Services\Dashboard\TenantDashboardService;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Models\TenantPlanoBeneficiario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Tabela do banco do tenant, criada no banco de teste (sem tenancy).
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->nullable();
            $table->string('cpf')->nullable();
            $table->boolean('status')->default(true);
            $table->string('status_registro')->nullable();
            $table->string('sexo')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('patients');

        parent::tearDown();
    }

    public function test_conta_so_beneficiarios_com_plano(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-dashboard']));
        TenantPlanoBeneficiario::withoutAuditing(fn () => TenantPlanoBeneficiario::create([
            'tenant_id' => $tenant->id, 'cod_plano' => '900001', 'nome' => 'Com plano', 'cpf' => '22222222222',
        ]));

        DB::table('patients')->insert([
            ['nome' => 'Com plano', 'cpf' => '222.222.222-22', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Sem plano', 'cpf' => '333.333.333-33', 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Sem CPF', 'cpf' => null, 'status' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $dados = app(TenantDashboardService::class)->getData($tenant, now()->year);

        $this->assertSame(1, $dados['totalPatients']);
        $this->assertSame(1, $dados['activePatients']);
        $this->assertSame(1, $dados['novosNoPeriodo']);
        $this->assertSame(1, array_sum($dados['monthlyGrowth']));
        $this->assertSame(1, array_sum(array_column($dados['porOrigem'], 'total')));
        $this->assertSame(1, array_sum(array_column($dados['porSexo'], 'total')));
    }

    public function test_cards_e_sms_seguem_o_periodo_do_filtro(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-dashboard-periodo']));
        TenantPlanoBeneficiario::withoutAuditing(function () use ($tenant) {
            foreach (['11111111111', '22222222222'] as $cpf) {
                TenantPlanoBeneficiario::create(['tenant_id' => $tenant->id, 'cod_plano' => '900001', 'nome' => 'Com plano', 'cpf' => $cpf]);
            }
        });

        DB::table('patients')->insert([
            ['nome' => 'Março', 'cpf' => '11111111111', 'status' => true, 'created_at' => '2026-03-10', 'updated_at' => '2026-03-10'],
            ['nome' => 'Maio', 'cpf' => '22222222222', 'status' => true, 'created_at' => '2026-05-10', 'updated_at' => '2026-05-10'],
        ]);
        DB::table('sms_logs')->insert([
            ['tenant_id' => $tenant->id, 'status' => SmsStatusEnum::Sent->value, 'created_at' => '2026-03-10', 'updated_at' => '2026-03-10'],
            ['tenant_id' => $tenant->id, 'status' => SmsStatusEnum::Sent->value, 'created_at' => '2026-05-10', 'updated_at' => '2026-05-10'],
        ]);

        $service = app(TenantDashboardService::class);
        $marco = $service->getData($tenant, 2026, 3);
        $ano = $service->getData($tenant, 2026);

        $this->assertSame('Março de 2026', $marco['periodoLabel']);
        $this->assertSame(1, $marco['totalPatients']);
        $this->assertSame(1, $marco['novosNoPeriodo']);
        $this->assertSame(1, $marco['sms']['enviados']);

        $this->assertSame('2026', $ano['periodoLabel']);
        $this->assertSame(2, $ano['totalPatients']);
        $this->assertSame(2, $ano['novosNoPeriodo']);
        $this->assertSame(2, $ano['sms']['enviados']);
    }

    public function test_planos_no_limite_vem_formatados(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-dashboard-limite']));
        TenantPlano::withoutAuditing(function () use ($tenant) {
            TenantPlano::create(['tenant_id' => $tenant->id, 'cod_plano' => '900001', 'quantidade' => 10, 'saldo' => 1]);
            TenantPlano::create(['tenant_id' => $tenant->id, 'cod_plano' => '900002', 'quantidade' => 10, 'saldo' => 5]);
        });

        $limite = app(TenantDashboardService::class)->getData($tenant, now()->year)['planosNoLimite'];

        $this->assertSame(1, $limite['total']);
        $this->assertStringContainsString('900001', $limite['descricao']);
        $this->assertStringNotContainsString('900002', $limite['descricao']);
    }

    public function test_sem_planos_no_limite_mostra_a_regra(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-dashboard-folga']));

        $limite = app(TenantDashboardService::class)->getData($tenant, now()->year)['planosNoLimite'];

        $this->assertSame(['total' => 0, 'descricao' => '10% ou menos das vagas livres'], $limite);
    }
}
