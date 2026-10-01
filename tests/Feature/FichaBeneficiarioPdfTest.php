<?php

namespace Tests\Feature;

use App\Enums\QuestionRoleEnum;
use App\Http\Services\Patient\FichaBeneficiarioPdfService;
use App\Models\Patient;
use App\Models\PatientAnswer;
use App\Models\Question;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FichaBeneficiarioPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_ficha_com_mascaras_datas_pt_br_e_pdf(): void
    {
        Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-ficha']));

        $patient = (new Patient)->forceFill([
            'id' => 7,
            'nome' => 'Maria da Silva',
            'cpf' => '12345678909',
            'rg' => '1234567',
            'email' => 'maria@example.com',
            'numero' => '86994311316',
            'sexo' => 'feminino',
            'data_nascimento' => '1990-05-10',
            'status' => true,
            'status_registro' => 'formulario',
            'enderecos' => ['cep' => '64000000', 'logradouro' => 'Rua A', 'numero' => '10', 'cidade' => 'Teresina', 'estado' => 'PI'],
            'created_at' => '2026-10-01 14:35:00',
            'updated_at' => '2026-10-01 15:00:00',
        ]);
        $patient->setRelation('answers', collect([
            $this->resposta('CPF do titular', '98765432100', QuestionRoleEnum::Cpf),
            $this->resposta('WhatsApp', '8632221234', QuestionRoleEnum::Tel),
            $this->resposta('Nascimento', '1990-05-10', QuestionRoleEnum::BirthDate),
            $this->resposta('Data da consulta', '2026-11-03', null),
        ]));

        $service = app(FichaBeneficiarioPdfService::class);
        $ficha = $service->dados($patient, 'tenant-ficha');

        $this->assertSame('123.456.789-09', $ficha['identificacao']['CPF']);
        $this->assertStringStartsWith('10/05/1990 (', $ficha['identificacao']['Data de nascimento']);
        $this->assertSame('Feminino', $ficha['identificacao']['Sexo']);
        $this->assertSame('(86) 99431-1316', $ficha['contato']['Celular']);
        $this->assertSame('64000-000', $ficha['endereco']['CEP']);
        $this->assertSame('Rua A, 10', $ficha['endereco']['Logradouro']);
        $this->assertSame('Teresina/PI', $ficha['endereco']['Cidade/UF']);
        $this->assertSame('01/10/2026 11:35', $ficha['cadastro']['Criado em']); // UTC -> Brasília
        $this->assertSame('01/10/2026 12:00', $ficha['cadastro']['Atualizado em']);
        $this->assertSame(
            ['987.654.321-00', '(86) 3222-1234', '10/05/1990', '03/11/2026'],
            array_column($ficha['respostas'], 'resposta'),
        );

        $html = view('pdf.ficha-beneficiario', ['ficha' => $ficha, 'tenant' => Tenant::find('tenant-ficha'), 'parceiro' => 'Parceiro Teste', 'logoBase64' => null])->render();
        $this->assertStringContainsString('Ficha do Beneficiário', $html);
        $this->assertStringContainsString('Sem plano vinculado', $html);
        $this->assertStringContainsString('Endereço', $html);

        $pdf = $service->gerar($patient, 'tenant-ficha')->output();
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    private function resposta(string $pergunta, string $valor, ?QuestionRoleEnum $papel): PatientAnswer
    {
        return (new PatientAnswer(['answer' => $valor]))
            ->setRelation('question', (new Question)->forceFill(['title' => $pergunta, 'role' => $papel]));
    }
}
