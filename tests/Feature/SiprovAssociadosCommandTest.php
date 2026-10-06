<?php

namespace Tests\Feature;

use App\Enums\QuestionRoleEnum;
use App\Models\CentralPatient;
use App\Models\CentralPatientAnswer;
use App\Models\Question;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantPlanoBeneficiario;
use App\Services\Siprov\SiprovAssociadoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SiprovAssociadosCommandTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-siprov']));

        $this->mock(SiprovAssociadoService::class, function (MockInterface $mock) {
            $mock->shouldReceive('todos')->once()->andReturn([
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

    public function test_completa_o_plano_do_vinculo_sem_plano_pelo_cpf(): void
    {
        $semPlano = $this->vinculo(['cpf_cnpj' => '12345678909']);
        $comPlano = $this->vinculo(['cpf_cnpj' => '123.456.789-09', 'cod_plano' => '331385', 'cod_planos' => ['331385'], 'plano_label' => 'Clínica Familiar']);
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
        $this->assertSame('Fulano', $data['title']);

        $this->assertSame(['331385'], $comPlano->fresh()->data['cod_planos']);
        $this->assertNull($siprovSemPlano->fresh()->data['cod_plano']);
    }

    public function test_simular_nao_grava(): void
    {
        $semPlano = $this->vinculo(['cpf_cnpj' => '12345678909']);

        $this->artisan('siprov:associados', ['--simular' => true])
            ->expectsOutputToContain('1 beneficiário(s) de parceiro seriam atualizados:')
            ->assertSuccessful();

        $this->assertNull($semPlano->fresh()->data['cod_plano']);
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

        $data = TelemedicinaTenant::sole()->data;
        $this->assertSame($this->tenant->id, TelemedicinaTenant::sole()->tenant_id);
        $this->assertSame('12345678909', $data['cpf_cnpj']);
        $this->assertSame(555, $data['siprov_id']);
        $this->assertSame(['331384'], $data['cod_planos']);
        $this->assertSame('MEDBEM - CLINICA ONLINE INDIVIDUAL', $data['plano_label']);
        $this->assertSame(777, $data['codBeneficio']);
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
