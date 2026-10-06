<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Services\Tenant\PlanoHistoricoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanoHistoricoServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlanoHistoricoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlanoHistoricoService::class);

        $this->registro('t1', 'José Silva', 'Ana Paula', '331385', '2026-09-28 09:00:00');
        $this->registro('t1', 'Maria Souza', 'Carlos', '331384', '2026-09-29 14:30:00');
        $this->registro('t1', null, 'Fulano', '331385', '2026-09-30 18:45:00'); // formulário público
        $this->registro('t2', 'José Silva', 'Outro tenant', '331385', '2026-09-29 10:00:00');
    }

    public function test_lista_apenas_do_tenant_mais_recentes_primeiro(): void
    {
        $this->assertSame(['Fulano', 'Carlos', 'Ana Paula'], $this->pacientes([]));
    }

    public function test_busca_por_quem_registrou_ou_paciente_sem_acento(): void
    {
        $this->assertSame(['Ana Paula'], $this->pacientes(['busca' => 'jose']));
        $this->assertSame(['Carlos'], $this->pacientes(['busca' => 'CARL']));
    }

    public function test_mostra_o_valor_do_plano_no_registro(): void
    {
        $this->registro('t3', null, 'Sem valor', '331384', '2026-10-01 09:00:00');
        $this->registro('t3', null, 'Com valor', '331384', '2026-10-01 10:00:00', 39.9);

        $this->assertSame(['R$ 39,90', null], array_column($this->service->listar('t3'), 'valor'));
    }

    public function test_filtro_por_plano(): void
    {
        $this->assertSame(['Fulano', 'Ana Paula'], $this->pacientes(['plano' => '331385']));
    }

    public function test_filtro_por_data_e_horario(): void
    {
        // Filtros em horário de Brasília (registros gravados em UTC, 3h à frente).
        $this->assertSame(['Carlos'], $this->pacientes(['de' => '2026-09-29T00:00', 'ate' => '2026-09-29T14:30']));
        $this->assertSame(['Fulano'], $this->pacientes(['de' => '2026-09-29T14:31']));
        $this->assertSame(['Ana Paula'], $this->pacientes(['ate' => '2026-09-28T09:00']));
    }

    public function test_filtros_combinados_e_data_invalida_ignorada(): void
    {
        $this->assertSame(['Fulano'], $this->pacientes(['plano' => '331385', 'de' => '2026-09-30T00:00']));
        $this->assertSame(['Fulano', 'Carlos', 'Ana Paula'], $this->pacientes(['de' => 'xx']));
    }

    public function test_totais_por_plano_respeitam_busca_e_periodo_mas_nao_o_plano(): void
    {
        $planos = [
            ['value' => '331385', 'label' => 'Clínica Familiar'],
            ['value' => '331384', 'label' => 'Clínica Individual'],
            ['value' => '331386', 'label' => 'Saúde Mental'],
        ];

        $todos = $this->service->totais('t1', [], $planos);
        $this->assertSame(3, $todos['total']);
        $this->assertSame([2, 1, 0], array_column($todos['planos'], 'total'));

        // Filtro de plano não zera os outros cards.
        $comPlano = $this->service->totais('t1', ['plano' => '331384'], $planos);
        $this->assertSame([2, 1, 0], array_column($comPlano['planos'], 'total'));

        $periodo = $this->service->totais('t1', ['de' => '2026-09-29T00:00'], $planos);
        $this->assertSame(2, $periodo['total']);
        $this->assertSame([1, 1, 0], array_column($periodo['planos'], 'total'));

        $busca = $this->service->totais('t1', ['busca' => 'jose'], $planos);
        $this->assertSame([1, 0, 0], array_column($busca['planos'], 'total'));
    }

    private function pacientes(array $filtros): array
    {
        return array_column($this->service->listar('t1', $filtros), 'paciente');
    }

    private function registro(string $tenant, ?string $usuario, string $paciente, string $codPlano, string $quando, ?float $valor = null): void
    {
        $audit = Audit::create([
            'event' => 'registro_plano',
            'auditable_type' => 'App\\Models\\TelemedicinaTenant',
            'auditable_id' => 1,
            'old_values' => [],
            'new_values' => [
                'usuario' => $usuario,
                'paciente' => $paciente,
                'cod_plano' => $codPlano,
                'plano' => 'Plano '.$codPlano,
                'origem' => $usuario ? 'cadastro_paciente' : 'formulario_publico',
                'planos' => [$codPlano => ['saldo' => 1, 'quantidade' => 2]],
                'valor' => $valor,
            ],
            'tags' => "tenant:{$tenant}",
        ]);

        $audit->forceFill(['created_at' => $quando])->save();
    }
}
