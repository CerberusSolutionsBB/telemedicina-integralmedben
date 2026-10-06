<?php

namespace Tests\Feature;

use App\Enums\QuestionRoleEnum;
use App\Models\Audit;
use App\Models\CentralPatient;
use App\Models\CentralPatientAnswer;
use App\Models\Question;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Models\TenantPlanoBeneficiario;
use App\Models\TenantQuantidadeParceiro;
use App\Services\Siprov\SiprovAssociadoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SiprovAssociadosCommandTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private TenantPlano $plano;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-siprov']));

        // Plano habilitado antes dos vínculos do teste: 50 vagas de R$ 39,90.
        $this->plano = TenantPlano::create(['tenant_id' => $this->tenant->id, 'cod_plano' => '331384', 'quantidade' => 50, 'saldo' => 50, 'valor' => 39.90]);
        $this->plano->forceFill(['created_at' => now()->subHour()])->save();

        $this->mock(SiprovAssociadoService::class, function (MockInterface $mock) {
            $mock->shouldReceive('todos')->andReturn([
                [
                    'nomePessoa' => 'Fulano',
                    'cpfCnpj' => '123.456.789-09',
                    'codPessoa' => 555,
                    'codBeneficio' => 777,
                    'situacao' => 'ATIVO',
                    'planos' => [['codPlano' => 331384, 'nome' => 'MEDBEM - CLINICA ONLINE INDIVIDUAL']],
                ],
                [
                    'nomePessoa' => 'Sem Plano',
                    'cpfCnpj' => '98765432100',
                    'codBeneficio' => 888,
                    'situacao' => 'ATIVO',
                    'planos' => [],
                ],
            ]);
        });
    }

    public function test_completa_o_plano_do_vinculo_sem_plano_e_desconta_a_vaga_com_valor(): void
    {
        $semPlano = $this->vinculo(['cpf_cnpj' => '12345678909']);
        $siprovSemPlano = $this->vinculo(['cpf_cnpj' => '98765432100']);

        $this->artisan('siprov:associados')
            ->expectsOutputToContain('2 associado(s) com benefício na SIPROV.')
            ->expectsOutputToContain('1 beneficiário(s) de parceiro atualizados:')
            ->assertSuccessful();

        $data = $semPlano->fresh()->data;
        $this->assertSame(331384, $data['cod_plano']);
        $this->assertSame(['331384'], $data['cod_planos']);
        $this->assertSame('MEDBEM - CLINICA ONLINE INDIVIDUAL', $data['plano_label']);
        $this->assertSame(777, $data['codBeneficio']);
        $this->assertNull($siprovSemPlano->fresh()->data['cod_plano']);

        $this->assertSame(49, $this->plano->fresh()->saldo);

        $movimento = TenantQuantidadeParceiro::where('tipo', TenantQuantidadeParceiro::TIPO_CONSUMO)->sole();
        $this->assertSame($semPlano->id, $movimento->telemedicina_tenant_id);
        $this->assertSame(-1, $movimento->variacao);
        $this->assertSame('39.90', $movimento->valor);
        $this->assertSame($this->plano->id, $movimento->plano_id);
        $this->assertSame($this->tenant->id, $movimento->tenant_id);
        $this->assertNull($movimento->user_id);
        $this->assertNotNull($movimento->created_at);

        $registro = Audit::where('event', 'registro_plano')->sole();
        $this->assertSame('sincronizacao_siprov', $registro->new_values['origem']);
        $this->assertEquals(39.90, $registro->new_values['valor']);
    }

    public function test_simular_nao_grava_nem_desconta(): void
    {
        $semPlano = $this->vinculo(['cpf_cnpj' => '12345678909']);

        $this->artisan('siprov:associados', ['--simular' => true])
            ->expectsOutputToContain('1 beneficiário(s) de parceiro seriam atualizados:')
            ->assertSuccessful();

        $this->assertNull($semPlano->fresh()->data['cod_plano']);
        $this->assertSame(50, $this->plano->fresh()->saldo);
        $this->assertSame(0, TenantQuantidadeParceiro::count());
    }

    public function test_cria_vinculo_com_plano_para_paciente_do_parceiro_sem_vinculo(): void
    {
        $outroTenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-interno']));
        $this->pacienteDoParceiro($this->tenant, '12345678909');
        $this->pacienteDoParceiro($outroTenant, '12345678909');
        $this->pacienteDoParceiro($this->tenant, '98765432100');
        TenantPlanoBeneficiario::create(['tenant_id' => $outroTenant->id, 'cod_plano' => '900001', 'nome' => 'Fulano', 'cpf' => '12345678909']);

        $this->artisan('siprov:associados')
            ->expectsOutputToContain('1 beneficiário(s) de parceiro atualizados:')
            ->assertSuccessful();

        $vinculo = TelemedicinaTenant::sole();
        $this->assertSame($this->tenant->id, $vinculo->tenant_id);
        $this->assertSame('12345678909', $vinculo->data['cpf_cnpj']);
        $this->assertSame(555, $vinculo->data['siprov_id']);
        $this->assertSame(['331384'], $vinculo->data['cod_planos']);
        $this->assertSame(777, $vinculo->data['codBeneficio']);
        $this->assertSame(49, $this->plano->fresh()->saldo);
    }

    public function test_desconta_vinculo_com_plano_que_nao_consumiu_a_vaga_uma_unica_vez(): void
    {
        $pendente = $this->vinculo(['cpf_cnpj' => '11122233344', 'cod_plano' => '331384', 'cod_planos' => ['331384']]);

        // Já estava no saldo inicial: vinculado antes de o plano ser habilitado.
        $anterior = $this->vinculo(['cpf_cnpj' => '55566677788', 'cod_plano' => '331384', 'cod_planos' => ['331384']]);
        $anterior->forceFill(['created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)])->saveQuietly();

        $this->artisan('siprov:associados')
            ->expectsOutputToContain('1 beneficiário(s) de parceiro atualizados:')
            ->assertSuccessful();

        $this->assertSame(49, $this->plano->fresh()->saldo);
        $this->assertSame($pendente->id, TenantQuantidadeParceiro::sole()->telemedicina_tenant_id);

        $this->artisan('siprov:associados')
            ->expectsOutputToContain('Nenhum parceiro com beneficiário sem plano para atualizar.')
            ->assertSuccessful();

        $this->assertSame(49, $this->plano->fresh()->saldo);
    }

    public function test_sem_vaga_mantem_o_plano_e_nao_desconta(): void
    {
        $this->plano->update(['saldo' => 0]);
        $semPlano = $this->vinculo(['cpf_cnpj' => '12345678909']);

        $this->artisan('siprov:associados')
            ->expectsOutputToContain('Não descontada')
            ->assertSuccessful();

        $this->assertSame(['331384'], $semPlano->fresh()->data['cod_planos']);
        $this->assertSame(0, $this->plano->fresh()->saldo);
        $this->assertSame(0, TenantQuantidadeParceiro::count());
    }

    public function test_nao_duplica_vinculo_existente_do_parceiro(): void
    {
        $this->pacienteDoParceiro($this->tenant, '12345678909');
        $this->vinculo(['cpf_cnpj' => '123.456.789-09', 'cod_plano' => '331385', 'cod_planos' => ['331385']]);

        $this->artisan('siprov:associados')
            ->expectsOutputToContain('Nenhum parceiro com beneficiário sem plano para atualizar.')
            ->assertSuccessful();

        $this->assertSame(1, TelemedicinaTenant::count());
    }

    private function pacienteDoParceiro(Tenant $tenant, string $cpf): void
    {
        $question = Question::firstOrCreate(['role' => QuestionRoleEnum::Cpf], ['title' => 'CPF', 'type' => 'text']);

        CentralPatientAnswer::create([
            'central_patient_id' => CentralPatient::create(['tenant_id' => $tenant->id])->id,
            'question_id' => $question->id,
            'answer' => $cpf,
        ]);
    }

    private function vinculo(array $data): TelemedicinaTenant
    {
        return TelemedicinaTenant::create([
            'tenant_id' => $this->tenant->id,
            'data' => [
                'siprov_id' => 123,
                'title' => 'Fulano',
                'cod_plano' => null,
                'cod_planos' => [],
                'plano_label' => '',
                'codBeneficio' => null,
                ...$data,
            ],
        ]);
    }
}
