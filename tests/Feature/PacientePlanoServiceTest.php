<?php

namespace Tests\Feature;

use App\Http\Requests\StorePatientRequest;
use App\Http\Services\ExternalApi\SiprovExternalService;
use App\Models\Audit;
use App\Models\ExternalApiLog;
use App\Models\Patient;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Models\User;
use App\Services\Tenant\TenantPlanoCotaService;
use App\Services\Tenant\PacientePlanoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_request_exige_plano_e_cpf_email_no_cadastro(): void
    {
        $semPlano = $this->validarRequest(['nome' => 'Fulano']);
        $this->assertArrayHasKey('cod_plano', $semPlano);

        $comPlano = $this->validarRequest(['nome' => 'Fulano', 'cod_plano' => '331385']);
        $this->assertArrayNotHasKey('cod_plano', $comPlano);
        $this->assertArrayHasKey('cpf', $comPlano);
        $this->assertArrayHasKey('email', $comPlano);

        $valido = $this->validarRequest([
            'nome' => 'Fulano', 'cod_plano' => '331385', 'cpf' => '12345678909', 'email' => 'f@example.com',
        ]);
        $this->assertSame([], $valido);
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
