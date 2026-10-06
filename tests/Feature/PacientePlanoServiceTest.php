<?php

namespace Tests\Feature;

use App\Http\Requests\StorePatientRequest;
use App\Http\Services\ExternalApi\SiprovExternalService;
use App\Models\Audit;
use App\Models\ExternalApiLog;
use App\Models\Patient;
use App\Models\Siprov;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Models\TenantPlanoBeneficiario;
use App\Models\User;
use App\Services\Tenant\PacientePlanoService;
use App\Services\Tenant\TenantPlanoCotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use Tests\TestCase;

class PacientePlanoServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-paciente']));
        $this->plano('331385', 1);
    }

    public function test_registra_na_siprov_e_adiciona_aos_associados(): void
    {
        $this->mock(SiprovExternalService::class, function (MockInterface $mock) {
            $mock->shouldReceive('registerPatient')
                ->once()
                ->withArgs(fn ($payload) => $payload['cpf'] === '12345678909' && $payload['plan'] === '331385')
                ->andReturn(['associado' => ['codPessoa' => 555], 'beneficio' => ['codBeneficio' => 777]]);
        });

        $erro = app(PacientePlanoService::class)->registrar($this->tenant->id, $this->patient(), '331385');

        $this->assertNull($erro);

        $vinculo = TelemedicinaTenant::where('tenant_id', $this->tenant->id)->sole();
        $this->assertSame(555, $vinculo->data['siprov_id']);
        $this->assertSame(['331385'], $vinculo->data['cod_planos']);
        $this->assertSame('12345678909', $vinculo->data['cpf_cnpj']);
        $this->assertSame('success', ExternalApiLog::where('api', 'siprov')->sole()->status);

        // A vaga foi consumida.
        $this->assertSame(0, app(PacientePlanoService::class)->opcoes($this->tenant->id)[0]['disponivel']);
    }

    public function test_falha_na_siprov_nao_cria_vinculo_e_retorna_erro(): void
    {
        $this->mock(SiprovExternalService::class, function (MockInterface $mock) {
            $mock->shouldReceive('registerPatient')->once()->andThrow(new \RuntimeException('SIPROV fora do ar'));
        });

        $erro = app(PacientePlanoService::class)->registrar($this->tenant->id, $this->patient(), '331385');

        $this->assertSame('SIPROV fora do ar', $erro);
        $this->assertSame(0, TelemedicinaTenant::count());
        $this->assertSame('failed', ExternalApiLog::where('api', 'siprov')->sole()->status);
    }

    public function test_validar_bloqueia_cota_esgotada_plano_nao_habilitado_e_cpf_ja_vinculado(): void
    {
        $service = app(PacientePlanoService::class);
        $data = ['nome' => 'Fulano', 'cpf' => '123.456.789-09'];

        $service->validar($this->tenant->id, '331385', $data);

        $this->assertValidationFails(fn () => $service->validar($this->tenant->id, '331384', $data));

        $vinculo = TelemedicinaTenant::create([
            'tenant_id' => $this->tenant->id,
            'data' => ['siprov_id' => 1, 'cpf_cnpj' => '12345678909', 'cod_plano' => '331385'],
        ]);
        app(TenantPlanoCotaService::class)->consumir($this->tenant->id, ['331385'], 7, $vinculo->id);

        // CPF já vinculado (também esgota a única vaga).
        $this->assertValidationFails(fn () => $service->validar($this->tenant->id, '331385', $data));
        $this->assertSame('331385', $service->vinculoAtual($this->tenant->id, '123.456.789-09')['cod_plano']);

        // Outro CPF: bloqueado pela cota.
        $this->assertValidationFails(fn () => $service->validar($this->tenant->id, '331385', ['nome' => 'Beltrano', 'cpf' => '98765432100']));
    }

    public function test_saldo_zero_bloqueia_cadastro_no_plano(): void
    {
        $service = app(PacientePlanoService::class);

        // Consome a única vaga do plano 331385 (saldo 1 -> 0).
        $vinculo = TelemedicinaTenant::create(['tenant_id' => $this->tenant->id, 'data' => ['siprov_id' => 1, 'cod_plano' => '331385']]);
        app(TenantPlanoCotaService::class)->consumir($this->tenant->id, ['331385'], 1, $vinculo->id);

        // Lista: plano aparece esgotado.
        $opcao = $service->opcoes($this->tenant->id)[0];
        $this->assertSame(0, $opcao['disponivel']);
        $this->assertTrue($opcao['esgotado']);

        // Validação antes de salvar: bloqueia.
        $this->assertValidationFails(fn () => $service->validar($this->tenant->id, '331385', ['nome' => 'Novo', 'cpf' => '98765432100']));

        // Mesmo passando da validação (corrida), o consumo não cria vínculo sem saldo.
        $this->mock(SiprovExternalService::class, fn (MockInterface $mock) => $mock->shouldReceive('registerPatient')->andReturn([]));
        $erro = app(PacientePlanoService::class)->registrar($this->tenant->id, $this->patient(), '331385');

        $this->assertStringContainsString('Limite do plano', $erro);
        $this->assertSame(1, TelemedicinaTenant::where('tenant_id', $this->tenant->id)->count());
        $this->assertSame(0, TenantPlano::where('cod_plano', '331385')->value('saldo'));
    }

    public function test_registro_no_cadastro_e_auditado_com_usuario_e_saldo(): void
    {
        config(['audit.console' => true]);
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->mock(SiprovExternalService::class, fn (MockInterface $mock) => $mock->shouldReceive('registerPatient')
            ->andReturn(['associado' => ['codPessoa' => 555]]));

        app(PacientePlanoService::class)->registrar($this->tenant->id, $this->patient(), '331385');

        $audit = Audit::where('event', 'registro_plano')->sole();

        $this->assertSame($user->id, (int) $audit->user_id);
        $this->assertSame($user->name, $audit->new_values['usuario']);
        $this->assertSame('Fulano de Tal', $audit->new_values['paciente']);
        $this->assertSame('tenant:'.$this->tenant->id, $audit->tags);
        $this->assertSame(42, $audit->new_values['paciente_id']);
        $this->assertSame('cadastro_paciente', $audit->new_values['origem']);
        $this->assertSame(0, $audit->new_values['planos']['331385']['saldo']);
        $this->assertSame(1, $audit->new_values['planos']['331385']['pacientes']);
    }

    public function test_plano_interno_nao_chama_siprov_e_consome_vaga(): void
    {
        config(['audit.console' => true]);
        $this->plano('beneficios', 2);
        $this->mock(SiprovExternalService::class, fn (MockInterface $mock) => $mock->shouldNotReceive('registerPatient'));

        $service = app(PacientePlanoService::class);
        $this->assertFalse(collect($service->opcoes($this->tenant->id))->firstWhere('value', 'beneficios')['siprov']);

        $this->assertNull($service->registrar($this->tenant->id, $this->patient(), 'beneficios'));

        $vinculo = TenantPlanoBeneficiario::sole();
        $this->assertSame(['beneficios', 42, '12345678909'], [$vinculo->cod_plano, $vinculo->patient_id, $vinculo->cpf]);
        $this->assertSame(0, TelemedicinaTenant::count()); // fora da lista de telemedicina/SIPROV
        $this->assertSame(1, TenantPlano::where('cod_plano', 'beneficios')->value('saldo'));
        $this->assertSame(0, ExternalApiLog::count());

        $audit = Audit::where('event', 'registro_plano')->sole();
        $this->assertSame('tenant:'.$this->tenant->id, $audit->tags);
        $this->assertSame('Plano de Benefícios', $audit->new_values['plano']);
        $this->assertSame(1, $audit->new_values['planos']['beneficios']['pacientes']);

        // Edit: plano só leitura; o mesmo CPF não pega outra vaga.
        $this->assertSame('beneficios', $service->vinculoAtual($this->tenant->id, '123.456.789-09')['cod_plano']);
        $this->assertValidationFails(fn () => $service->validar($this->tenant->id, 'beneficios', ['nome' => 'Fulano', 'cpf' => '12345678909']));
    }

    public function test_plano_interno_sem_saldo_bloqueia(): void
    {
        $this->plano('beneficios', 1);
        $service = app(PacientePlanoService::class);
        $service->registrarVinculoInterno($this->tenant->id, 'beneficios', 'Outro', '98765432100', 7, 'cadastro_paciente');

        $this->assertValidationFails(fn () => $service->validar($this->tenant->id, 'beneficios', ['nome' => 'Novo', 'cpf' => '11122233344']));
        $this->assertStringContainsString('Limite do plano Plano de Benefícios', $service->registrar($this->tenant->id, $this->patient(), 'beneficios'));
        $this->assertSame(1, TenantPlanoBeneficiario::count());
    }

    public function test_detalhes_do_plano_e_do_cadastro(): void
    {
        config(['audit.console' => true]);
        Carbon::setTestNow('2026-10-01 14:35:00');
        $user = User::factory()->create(['name' => 'Atendente Ana']);
        $this->actingAs($user);
        $this->mock(SiprovExternalService::class, fn (MockInterface $mock) => $mock->shouldReceive('registerPatient')->andReturn([]));

        $service = app(PacientePlanoService::class);
        $patient = $this->patient();
        $service->registrar($this->tenant->id, $patient, '331385');

        $this->assertSame([
            'plano' => 'Clínica Familiar',
            'siprov' => true,
            'origem' => 'Cadastro manual',
            'origem_tipo' => 'cadastro_paciente',
            'usuario' => 'Atendente Ana',
            'data_hora' => '01/10/2026 11:35', // 14:35 UTC em Brasília
        ], $service->detalhes($this->tenant->id, $patient)['plano']);

        // Cadastro: auditoria "created" do paciente com a tag do tenant (outro tenant com o mesmo id é ignorado).
        $this->auditoriaCadastro(42, 'outro-tenant', null, '2026-01-01 08:00:00');
        $this->auditoriaCadastro(42, $this->tenant->id, $user->id, '2026-10-01 09:10:00');
        $this->assertSame(
            ['usuario' => 'Atendente Ana', 'data_hora' => '01/10/2026 06:10', 'auditado' => true],
            $service->detalhes($this->tenant->id, $patient)['cadastro'],
        );

        Carbon::setTestNow();
    }

    public function test_detalhes_plano_interno_sem_plano_e_cadastro_sem_auditoria(): void
    {
        $this->plano('beneficios', 5);
        $service = app(PacientePlanoService::class);
        $service->registrarVinculoInterno($this->tenant->id, 'beneficios', 'Fulano', '12345678909', 42, 'cadastro_paciente');

        $patient = $this->patient()->forceFill(['created_at' => '2026-05-20 10:00:00']);
        $detalhes = $service->detalhes($this->tenant->id, $patient);
        $this->assertSame(['Plano de Benefícios', false], [$detalhes['plano']['plano'], $detalhes['plano']['siprov']]);
        $this->assertSame(['usuario' => null, 'data_hora' => '20/05/2026 07:00', 'auditado' => false], $detalhes['cadastro']);

        $semPlano = $this->patient()->forceFill(['cpf' => '000.000.000-00']);
        $this->assertNull($service->detalhes($this->tenant->id, $semPlano)['plano']);
    }

    public function test_cadastro_aceita_auditoria_antiga_sem_tag_so_no_mesmo_instante(): void
    {
        $user = User::factory()->create(['name' => 'Antes da tag']);
        $service = app(PacientePlanoService::class);
        $patient = $this->patient()->forceFill(['created_at' => '2026-09-30 22:00:00']);

        // Sem tag, mas em outro instante (outro tenant com o mesmo id): ignorada.
        Audit::create(['event' => 'created', 'auditable_type' => Patient::class, 'auditable_id' => 42,
            'user_type' => User::class, 'user_id' => $user->id, 'old_values' => [], 'new_values' => []])
            ->forceFill(['created_at' => '2026-09-30 10:00:00'])->save();
        $this->assertFalse($service->detalhes($this->tenant->id, $patient)['cadastro']['auditado']);

        // Sem tag e no mesmo instante da criação: aceita.
        Audit::create(['event' => 'created', 'auditable_type' => Patient::class, 'auditable_id' => 42,
            'user_type' => User::class, 'user_id' => $user->id, 'old_values' => [], 'new_values' => []])
            ->forceFill(['created_at' => '2026-09-30 22:00:02'])->save();
        $this->assertSame('Antes da tag', $service->detalhes($this->tenant->id, $patient)['cadastro']['usuario']);
    }

    private function auditoriaCadastro(int $patientId, string $tenant, ?int $userId, string $quando): void
    {
        $audit = Audit::create([
            'event' => 'created',
            'auditable_type' => Patient::class,
            'auditable_id' => $patientId,
            'user_type' => $userId ? User::class : null,
            'user_id' => $userId,
            'old_values' => [],
            'new_values' => [],
            'tags' => "tenant:{$tenant}",
        ]);
        $audit->forceFill(['created_at' => $quando])->save();
    }

    private function assertValidationFails(callable $fn): void
    {
        try {
            $fn();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cod_plano', $e->errors());

            return;
        }

        $this->fail('ValidationException esperada.');
    }

    private function patient(): Patient
    {
        return (new Patient)->forceFill([
            'id' => 42,
            'nome' => 'Fulano de Tal',
            'cpf' => '123.456.789-09',
            'email' => 'fulano@example.com',
            'numero' => '11999999999',
            'sexo' => 'masculino',
            'data_nascimento' => '1990-05-10',
        ]);
    }

    public function test_request_exige_plano_cpf_e_nascimento_mas_nao_email(): void
    {
        $semPlano = $this->validarRequest(['nome' => 'Fulano']);
        $this->assertArrayHasKey('cod_plano', $semPlano);
        $this->assertArrayHasKey('data_nascimento', $semPlano);

        $comPlano = $this->validarRequest(['nome' => 'Fulano', 'cod_plano' => '331385']);
        $this->assertArrayNotHasKey('cod_plano', $comPlano);
        $this->assertArrayHasKey('cpf', $comPlano);
        $this->assertArrayNotHasKey('email', $comPlano);

        $futuro = $this->validarRequest(['nome' => 'Fulano', 'data_nascimento' => now()->addDay()->toDateString()]);
        $this->assertSame(['A data de nascimento não pode estar no futuro.'], $futuro['data_nascimento']);

        // Sem e-mail: válido.
        $valido = $this->validarRequest([
            'nome' => 'Fulano', 'cod_plano' => '331385', 'cpf' => '12345678909', 'data_nascimento' => '1990-05-10',
        ]);
        $this->assertSame([], $valido);
    }

    public function test_beneficiario_sem_vinculo_usa_o_plano_do_registro_da_siprov(): void
    {
        Siprov::create([
            'codigo_integracao' => 'USR-12345678909',
            'nome_pessoa' => 'Fulano',
            'cpf_cnpj' => '123.456.789-09',
            'cod_plano' => '331386',
            'status' => Siprov::STATUS_SUCCESS,
            'integrated_at' => now(),
        ]);

        $planos = app(PacientePlanoService::class)->planosPorCpf($this->tenant->id, ['12345678909']);

        $this->assertSame('Saúde Mental', $planos['12345678909']);
    }

    public function test_vinculo_do_parceiro_tem_prioridade_sobre_o_registro_da_siprov(): void
    {
        TelemedicinaTenant::create([
            'tenant_id' => $this->tenant->id,
            'data' => ['cpf_cnpj' => '12345678909', 'cod_planos' => ['331385'], 'plano_label' => 'Clínica Familiar'],
        ]);

        Siprov::create([
            'codigo_integracao' => 'USR-12345678909',
            'nome_pessoa' => 'Fulano',
            'cpf_cnpj' => '12345678909',
            'cod_plano' => '331386',
            'status' => Siprov::STATUS_SUCCESS,
            'integrated_at' => now(),
        ]);

        $planos = app(PacientePlanoService::class)->planosPorCpf($this->tenant->id, ['12345678909']);

        $this->assertSame('Clínica Familiar', $planos['12345678909']);
    }

    private function validarRequest(array $data): array
    {
        $request = StorePatientRequest::create('/patients', 'POST', $data);
        $request->setRouteResolver(fn () => null);

        return Validator::make($data, $request->rules(), $request->messages())->errors()->toArray();
    }

    private function plano(string $codPlano, int $quantidade): void
    {
        app(TenantPlanoCotaService::class)->ajustarQuantidade($this->tenant->id, $codPlano, $quantidade);
    }
}
