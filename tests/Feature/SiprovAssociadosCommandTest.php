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
use App\Services\Siprov\SiprovPlanoVinculoService;
use App\Services\Tenant\PacientePlanoService;
use App\Services\Tenant\PacientesDoParceiroService;
use App\Services\Tenant\TenantPlanoCotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SiprovAssociadosCommandTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private TenantPlano $plano;

    /**
     * CPFs cadastrados como pacientes no banco de cada parceiro.
     *
     * @var array<string, array<int, string>>
     */
    private array $pacientes = ['tenant-siprov' => ['12345678909', '11122233344']];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-siprov']));

        // Plano habilitado antes dos vínculos do teste: 50 vagas de R$ 39,90.
        $this->plano = TenantPlano::create(['tenant_id' => $this->tenant->id, 'cod_plano' => '331384', 'quantidade' => 50, 'saldo' => 50, 'valor' => 39.90]);
        $this->plano->forceFill(['created_at' => now()->subHour()])->save();

        $this->mock(PacientesDoParceiroService::class, function (MockInterface $mock) {
            $mock->shouldReceive('ehPaciente')->andReturnUsing(
                fn (string $tenantId, ?string $cpf) => in_array(preg_replace('/\D/', '', (string) $cpf), $this->pacientes[$tenantId] ?? [], true)
            );
        });

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

    public function test_nao_desconta_vinculo_de_quem_nao_e_paciente_do_parceiro(): void
    {
        $this->vinculo(['cpf_cnpj' => '99988877766', 'cod_plano' => '331384', 'cod_planos' => ['331384']]);

        $this->artisan('siprov:associados')
            ->expectsOutputToContain('Nenhum parceiro com beneficiário sem plano para atualizar.')
            ->assertSuccessful();

        $this->assertSame(50, $this->plano->fresh()->saldo);
        $this->assertSame(0, TenantQuantidadeParceiro::count());
    }

    public function test_devolve_vaga_descontada_pela_sincronizacao_para_quem_nao_e_paciente(): void
    {
        $vinculo = $this->vinculo(['cpf_cnpj' => '99988877766', 'cod_plano' => '331384', 'cod_planos' => ['331384']]);

        // Desconto feito por engano por uma execução anterior da sincronização.
        app(TenantPlanoCotaService::class)->consumir($this->tenant->id, ['331384'], null, $vinculo->id);
        app(PacientePlanoService::class)->auditarRegistro($vinculo, $this->tenant->id, '331384', 'Clínica Individual', 'Fulano', null, SiprovPlanoVinculoService::ORIGEM);
        $this->assertSame(49, $this->plano->fresh()->saldo);

        $this->artisan('siprov:associados')
            ->expectsOutputToContain('Vaga devolvida')
            ->assertSuccessful();

        $this->assertSame(50, $this->plano->fresh()->saldo);
        $devolucao = TenantQuantidadeParceiro::where('tipo', TenantQuantidadeParceiro::TIPO_DEVOLUCAO)->sole();
        $this->assertSame($vinculo->id, $devolucao->telemedicina_tenant_id);
        $this->assertSame('39.90', $devolucao->valor);
        $this->assertSame(0, Audit::where('event', 'registro_plano')->count());

        $this->artisan('siprov:associados')
            ->expectsOutputToContain('Nenhum parceiro com beneficiário sem plano para atualizar.')
            ->assertSuccessful();

        $this->assertSame(50, $this->plano->fresh()->saldo);
    }

    public function test_registra_no_historico_paciente_que_ja_ocupa_vaga_sem_registro(): void
    {
        // Vínculo pelo modal: vaga descontada, sem registro no histórico.
        $modal = $this->vinculo(['cpf_cnpj' => '11122233344', 'cod_plano' => '331384', 'cod_planos' => ['331384']]);
        app(TenantPlanoCotaService::class)->consumir($this->tenant->id, ['331384'], null, $modal->id);
        $consumo = TenantQuantidadeParceiro::sole();
        $consumo->forceFill(['created_at' => '2026-07-26 14:33:00'])->save();

        // Já existia quando o plano foi habilitado (saldo inicial).
        $inicial = $this->vinculo(['cpf_cnpj' => '12345678909', 'cod_plano' => '331384', 'cod_planos' => ['331384']]);
        $inicial->forceFill(['created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)])->saveQuietly();

        $this->artisan('siprov:associados')
            ->expectsOutputToContain('2 beneficiário(s) de parceiro atualizados:')
            ->assertSuccessful();

        $this->assertSame(49, $this->plano->fresh()->saldo);
        $this->assertSame(1, TenantQuantidadeParceiro::count());

        $registroModal = Audit::where('event', 'registro_plano')->where('auditable_id', $modal->id)->sole();
        $this->assertSame('2026-07-26 14:33:00', $registroModal->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('vinculo_siprov', $registroModal->new_values['origem']);
        $this->assertSame(49, $registroModal->new_values['planos']['331384']['saldo']);
        $this->assertEquals(39.90, $registroModal->new_values['valor']);
        // Sem movimento: saldo inicial do plano refeito pelo extrato (antes do 1º consumo).
        $registroInicial = Audit::where('event', 'registro_plano')->where('auditable_id', $inicial->id)->sole();
        $this->assertSame(['plano' => 'Clínica Individual', 'quantidade' => 50, 'saldo' => 50], $registroInicial->new_values['planos']['331384']);

        $this->artisan('siprov:associados')
            ->expectsOutputToContain('Nenhum parceiro com beneficiário sem plano para atualizar.')
            ->assertSuccessful();
    }

    public function test_completa_saldo_inicial_de_registro_ja_criado_sem_saldo(): void
    {
        // Vínculo existente quando o plano foi habilitado com 20 vagas; depois o contratado subiu para 25.
        $vinculo = $this->vinculo(['cpf_cnpj' => '12345678909', 'cod_plano' => '331385', 'cod_planos' => ['331385']]);
        $cota = app(TenantPlanoCotaService::class);
        $cota->ajustarQuantidade($this->tenant->id, '331385', 20);
        $cota->ajustarQuantidade($this->tenant->id, '331385', 25);

        Audit::create([
            'event' => 'registro_plano',
            'auditable_type' => TelemedicinaTenant::class,
            'auditable_id' => $vinculo->id,
            'old_values' => [],
            'new_values' => ['paciente' => 'Fulano', 'origem' => 'vinculo_siprov', 'cod_plano' => '331385', 'plano' => 'Clínica Familiar', 'planos' => []],
            'tags' => 'tenant:'.$this->tenant->id,
        ]);

        $this->artisan('siprov:associados')
            ->expectsOutputToContain('Histórico atualizado')
            ->assertSuccessful();

        $planos = Audit::where('event', 'registro_plano')->sole()->new_values['planos'];
        $this->assertSame(['plano' => 'Clínica Familiar', 'quantidade' => 20, 'saldo' => 19], $planos['331385']);

        $this->artisan('siprov:associados')
            ->expectsOutputToContain('Nenhum parceiro com beneficiário sem plano para atualizar.')
            ->assertSuccessful();
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
        $this->assertSame(0, Audit::where('event', 'registro_plano')->count());
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
