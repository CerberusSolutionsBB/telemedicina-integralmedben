<?php

namespace Tests\Feature;

use App\Http\Requests\StorePatientRequest;
use App\Http\Services\Sms\PatientRegistrationSmsService;
use App\Models\Patient;
use App\Models\SmsLogs;
use App\Models\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PatientRegistrationSmsServiceTest extends TestCase
{
    use RefreshDatabase;

    private const DEVYX_URL = 'https://endpoint.devyx.com.br/api/send';

    protected function setUp(): void
    {
        parent::setUp();

        // Tabela do banco do tenant, criada no banco de teste (sem tenancy).
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->nullable();
            $table->string('numero')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        config([
            'services.devyx.url' => 'https://endpoint.devyx.com.br',
            'services.devyx.token' => 'token-de-teste',
            'services.devyx.source_addr' => null,
            'services.devyx.callback_url' => null,
            'services.devyx.callback_level' => null,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('patients');

        parent::tearDown();
    }

    public function test_envia_confirmacao_do_cadastro(): void
    {
        Http::fake([self::DEVYX_URL => Http::response(['message_id' => 'msg-1'])]);

        $tenant = $this->tenant(2);
        $patient = $this->patient('(86) 98888-7777');

        $result = app(PatientRegistrationSmsService::class)->execute($patient, $tenant->id, '331385');

        $this->assertTrue($result['sent']);
        $this->assertSame('msg-1', $result['message_id']);

        Http::assertSent(fn ($request) => $request->url() === self::DEVYX_URL
            && $request['destination_addr'] === '5586988887777'
            && str_contains($request['message'], 'Clínica Teste, Olá Fulano de Tal!')
            && str_contains($request['message'], 'no plano Clínica Familiar'));

        $log = SmsLogs::sole();
        $this->assertSame('sent', $log->status->value);
        $this->assertSame($patient->id, $log->patient_id);
        $this->assertSame('(86) 98888-7777', $log->recipient);
    }

    public function test_sem_cota_nao_envia_e_registra_a_falha(): void
    {
        Http::fake();

        $tenant = $this->tenant(0);

        $result = app(PatientRegistrationSmsService::class)->execute($this->patient('(86) 98888-7777'), $tenant->id, '331385');

        $this->assertFalse($result['sent']);
        $this->assertSame('Cota de SMS esgotada.', $result['error']);
        Http::assertNothingSent();
        $this->assertSame('failed', SmsLogs::sole()->status->value);
    }

    public function test_beneficiario_sem_celular_nao_envia(): void
    {
        Http::fake();

        $result = app(PatientRegistrationSmsService::class)->execute($this->patient(null), $this->tenant(5)->id, '331385');

        $this->assertFalse($result['sent']);
        $this->assertStringContainsString('celular', $result['error']);
        Http::assertNothingSent();
        $this->assertSame(0, SmsLogs::count());
    }

    public function test_opcao_marcada_exige_celular_valido(): void
    {
        $base = [
            'nome' => 'Fulano',
            'cod_plano' => '331385',
            'cpf' => '12345678909',
            'data_nascimento' => '1990-05-10',
        ];

        $semCelular = $this->validarRequest([...$base, 'enviar_sms' => true]);
        $this->assertSame(
            ['Informe o celular do beneficiário para enviar o SMS.'],
            $semCelular['numero'],
        );

        $invalido = $this->validarRequest([...$base, 'enviar_sms' => true, 'numero' => '1234']);
        $this->assertArrayHasKey('numero', $invalido);

        // Fixo não recebe SMS: precisa ser celular.
        $fixo = $this->validarRequest([...$base, 'enviar_sms' => true, 'numero' => '(86) 3222-1234']);
        $this->assertSame(['Informe um celular válido para receber o SMS.'], $fixo['numero']);

        $valido = $this->validarRequest([...$base, 'enviar_sms' => true, 'numero' => '(86) 98888-7777']);
        $this->assertArrayNotHasKey('numero', $valido);

        // Sem marcar a opção, o celular continua opcional.
        $semOpcao = $this->validarRequest($base);
        $this->assertArrayNotHasKey('numero', $semOpcao);
    }

    private function tenant(int $smsQuota): Tenant
    {
        return Tenant::withoutEvents(fn () => Tenant::create([
            'id' => 'tenant-sms-'.$smsQuota,
            'sms_quota' => $smsQuota,
            'name' => 'Clínica Teste',
        ]));
    }

    private function patient(?string $numero): Patient
    {
        return Patient::withoutAuditing(fn () => Patient::create([
            'nome' => 'Fulano de Tal',
            'numero' => $numero,
            'data_nascimento' => '1990-05-10',
        ]));
    }

    private function validarRequest(array $data): array
    {
        $request = StorePatientRequest::create('/patients', 'POST', $data);
        $request->setRouteResolver(fn () => null);

        return Validator::make($data, $request->rules(), $request->messages())->errors()->toArray();
    }
}
