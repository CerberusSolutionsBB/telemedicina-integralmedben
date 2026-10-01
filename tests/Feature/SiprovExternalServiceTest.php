<?php

namespace Tests\Feature;

use App\Data\SiprovIntegrationData;
use App\Http\Services\ExternalApi\SiprovExternalService;
use App\Models\Siprov;
use App\Services\Siprov\SiprovIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SiprovExternalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_sem_email_enviando_vazio(): void
    {
        $this->mock(SiprovIntegrationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')->once()
                ->withArgs(fn (SiprovIntegrationData $dto) => $dto->email === '' && $dto->cpfCnpj === '12345678909')
                ->andReturn(['associado' => [], 'beneficio' => []]);
        });

        app(SiprovExternalService::class)->registerPatient([
            'nome' => 'Fulano', 'cpf' => '123.456.789-09', 'email' => null, 'plan' => '331385', 'birth_date' => '1990-05-10',
        ]);

        $this->assertSame(Siprov::STATUS_SUCCESS, Siprov::where('cpf_cnpj', '12345678909')->value('status'));
    }

    public function test_cpf_continua_obrigatorio(): void
    {
        $this->expectExceptionMessage('SIPROV: campos obrigatórios ausentes (nome, cpf).');

        app(SiprovExternalService::class)->registerPatient(['nome' => 'Fulano', 'email' => 'f@example.com', 'plan' => '331385']);
    }
}
