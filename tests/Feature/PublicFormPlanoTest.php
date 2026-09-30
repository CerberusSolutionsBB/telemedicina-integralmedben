<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantPlano;
use App\Models\TenantQuantidadeParceiro;
use App\Services\Siprov\SiprovIntegrationService;
use App\Services\Tenant\TenantPlanoCotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PublicFormPlanoTest extends TestCase
{
    use RefreshDatabase;

    private Form $form;

    private int $campoNome;

    private int $campoCpf;

    protected function setUp(): void
    {
        parent::setUp();

        Tenant::withoutEvents(fn () => Tenant::create(['id' => 'formtenant']));
        app(TenantPlanoCotaService::class)->ajustarQuantidade('formtenant', '331385', 1);

        $this->form = Form::factory()->create([
            'status' => 'ativo',
            'is_public' => true,
            'expires_at' => null,
            'response_limit' => null,
            'status_beneficio' => true,
            'plano_id' => '331385',
        ]);
        $this->campoNome = $this->form->fields()->create(['type' => 'text', 'label' => 'Nome completo', 'required' => true, 'order' => 1])->id;
        $this->campoCpf = $this->form->fields()->create(['type' => 'cpf', 'label' => 'CPF', 'required' => true, 'order' => 2])->id;
    }

    public function test_resposta_com_plano_decrementa_saldo_e_vincula_associado(): void
    {
        $this->siprov(1);

        $this->responder('formtenant', 'Fulano', '12345678909')
            ->assertRedirect(route('forms.public.thanks', $this->form->slug));

        $this->assertSame(0, TenantPlano::where('tenant_id', 'formtenant')->value('saldo'));

        $vinculo = TelemedicinaTenant::where('tenant_id', 'formtenant')->sole();
        $this->assertSame('formulario_publico', $vinculo->data['origem']);
        $this->assertSame(['331385'], $vinculo->data['cod_planos']);

        $this->assertSame(1, TenantQuantidadeParceiro::where('tipo', 'consumo')
            ->where('telemedicina_tenant_id', $vinculo->id)->count());
    }

    public function test_saldo_zero_bloqueia_a_resposta(): void
    {
        $this->siprov(1);
        $this->responder('formtenant', 'Fulano', '12345678909');

        // Sem vagas: a segunda resposta nem é salva, e a SIPROV não é chamada de novo.
        $this->responder('formtenant', 'Beltrano', '98765432100')->assertSessionHasErrors('plano');

        $this->assertSame(1, FormResponse::where('form_id', $this->form->id)->count());
        $this->assertSame(1, TelemedicinaTenant::count());
    }

    public function test_plano_nao_habilitado_no_tenant_bloqueia_a_resposta(): void
    {
        $this->form->update(['plano_id' => '331386']);
        $this->siprov(0);

        $this->responder('formtenant', 'Fulano', '12345678909')->assertSessionHasErrors('plano');

        $this->assertSame(0, FormResponse::count());
    }

    public function test_fora_de_tenant_nao_controla_saldo(): void
    {
        $this->siprov(1);

        $this->responder(null, 'Fulano', '12345678909')
            ->assertRedirect(route('forms.public.thanks', $this->form->slug));

        $this->assertSame(1, TenantPlano::where('tenant_id', 'formtenant')->value('saldo'));
        $this->assertSame(0, TelemedicinaTenant::count());
    }

    public function test_registro_pelo_formulario_e_auditado_com_ip_dispositivo_e_planos(): void
    {
        config(['audit.console' => true]);
        $this->siprov(1);

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'])
            ->withServerVariables(['REMOTE_ADDR' => '200.10.20.30'])
            ->post("http://formtenant.localhost/forms/f/{$this->form->slug}", [
                'answers' => [$this->campoNome => 'Fulano', $this->campoCpf => '12345678909'],
            ]);

        $audit = Audit::where('event', 'registro_plano')->sole();

        $this->assertSame('200.10.20.30', $audit->ip_address);
        $this->assertStringContainsString('iPhone', $audit->user_agent);
        $this->assertSame(['tipo' => 'celular', 'sistema' => 'iOS', 'navegador' => 'Safari'], $audit->dispositivo);
        $this->assertNull($audit->user_id); // formulário público: sem login

        $this->assertSame('tenant:formtenant', $audit->tags);
        $this->assertSame('Fulano', $audit->new_values['paciente']);
        $this->assertNull($audit->new_values['usuario']);
        $this->assertSame('formulario_publico', $audit->new_values['origem']);
        $this->assertSame(
            ['plano' => 'Clínica Familiar', 'quantidade' => 1, 'saldo' => 0, 'em_uso' => 1, 'pacientes' => 1],
            $audit->new_values['planos']['331385'],
        );
    }

    private function siprov(int $chamadas): void
    {
        $this->mock(SiprovIntegrationService::class, function (MockInterface $mock) use ($chamadas) {
            $mock->shouldReceive('execute')->times($chamadas)
                ->andReturn(['associado' => ['codPessoa' => 9], 'beneficio' => ['codBeneficio' => 8]]);
        });
    }

    private function responder(?string $tenant, string $nome, string $cpf)
    {
        $host = $tenant ? "http://{$tenant}.localhost" : 'http://localhost';

        return $this->post("{$host}/forms/f/{$this->form->slug}", [
            'answers' => [$this->campoNome => $nome, $this->campoCpf => $cpf],
        ]);
    }
}
