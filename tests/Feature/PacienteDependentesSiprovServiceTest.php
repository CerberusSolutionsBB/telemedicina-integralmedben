<?php

namespace Tests\Feature;

use App\Models\ExternalApiLog;
use App\Models\PacienteVinculoFamiliar;
use App\Models\Patient;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Services\Siprov\SiprovAuthService;
use App\Services\Tenant\PacienteDependentesSiprovService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PacienteDependentesSiprovServiceTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://siprov.teste/api';

    private Tenant $tenant;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        // Tabelas do banco do tenant, criadas no banco de teste (sem tenancy).
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->nullable();
            $table->string('cpf')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('paciente_vinculo_familiares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paciente_id');
            $table->string('plano_id');
            $table->string('nome');
            $table->string('cpf', 14)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('sexo', 10)->nullable();
            $table->string('tipo', 30);
            $table->unsignedInteger('siprov_cod_dependente')->nullable();
            $table->timestamps();
        });

        config(['siprov.base_url' => self::BASE]);
        $this->mock(SiprovAuthService::class, fn ($mock) => $mock->shouldReceive('token')->andReturn('token-teste'));

        $this->tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-dependentes']));
        $this->patient = Patient::withoutAuditing(fn () => Patient::create(['nome' => 'Titular', 'cpf' => '123.456.789-09']));
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('paciente_vinculo_familiares');
        Schema::dropIfExists('patients');

        parent::tearDown();
    }

    private function vinculoFamiliar(): void
    {
        TelemedicinaTenant::withoutAuditing(fn () => TelemedicinaTenant::create([
            'tenant_id' => $this->tenant->id,
            'data' => ['cpf_cnpj' => '12345678909', 'cod_plano' => '331385', 'cod_planos' => ['331385'], 'codBeneficio' => 777],
        ]));
    }

    private function familiar(array $dados = []): PacienteVinculoFamiliar
    {
        return $this->patient->familiares()->create($dados + [
            'plano_id' => '331385',
            'nome' => 'Ana Titular',
            'cpf' => '98765432100',
            'data_nascimento' => '2015-04-03',
            'sexo' => 'Feminino',
            'tipo' => 'FILHO',
        ]);
    }

    private function enviar(array $removidos = []): ?string
    {
        return app(PacienteDependentesSiprovService::class)->enviar($this->tenant->id, $this->patient, $removidos);
    }

    public function test_cria_dependente_no_beneficio_do_titular_e_guarda_o_codigo(): void
    {
        $this->vinculoFamiliar();
        $familiar = $this->familiar();
        Http::fake([self::BASE.'/ext/beneficio/dependente' => Http::response(['codDependente' => 55, 'numeroCartaoDesconto' => '1'])]);

        $this->assertNull($this->enviar());

        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && $r->url() === self::BASE.'/ext/beneficio/dependente'
            && $r->hasHeader('Authorization', 'Bearer token-teste')
            && $r->data() === [
                'codBeneficio' => 777,
                'nome' => 'Ana Titular',
                'cpf' => '98765432100',
                'dataNascimento' => '03/04/2015',
                'parentesco' => 'FILHO',
                'sexo' => 'Feminino',
                'planos' => [331385],
                'ativo' => true,
            ]);
        $this->assertSame(55, $familiar->fresh()->siprov_cod_dependente);
        $this->assertSame('success', ExternalApiLog::where('api', 'siprov_dependente')->value('status'));
    }

    public function test_reenvio_atualiza_pelo_codigo_e_inativa_os_removidos(): void
    {
        $this->vinculoFamiliar();
        $this->familiar(['siprov_cod_dependente' => 55, 'tipo' => 'CONJUGE', 'sexo' => null, 'nome' => 'Cônjuge']);
        Http::fake([
            self::BASE.'/ext/beneficio/777/dependentes' => Http::response([
                ['codDependente' => 90, 'nome' => 'Removido', 'parentesco' => 'Filho(a)', 'ativo' => true],
            ]),
            self::BASE.'/ext/beneficio/dependente' => Http::response(['codDependente' => 55]),
        ]);

        $this->assertNull($this->enviar([90]));

        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && ($r->data()['codDependente'] ?? null) === 55
            && $r->data()['parentesco'] === 'CONJUGE'
            && ! isset($r->data()['sexo']));
        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && ($r->data()['codDependente'] ?? null) === 90
            && $r->data()['ativo'] === false);
    }

    public function test_sem_beneficio_familiar_nao_envia_nada(): void
    {
        $this->familiar();
        Http::fake();

        $this->assertNull($this->enviar());

        Http::assertNothingSent();
    }

    public function test_falha_na_siprov_e_relatada_sem_perder_o_familiar(): void
    {
        $this->vinculoFamiliar();
        $familiar = $this->familiar();
        Http::fake([self::BASE.'/ext/beneficio/dependente' => Http::response('Parentesco inválido', 422)]);

        $erro = $this->enviar();

        $this->assertStringContainsString('Ana Titular', $erro);
        $this->assertNull($familiar->fresh()->siprov_cod_dependente);
        $this->assertSame('failed', ExternalApiLog::where('api', 'siprov_dependente')->value('status'));
    }
}
