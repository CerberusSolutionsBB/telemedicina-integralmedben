<?php

namespace Tests\Feature;

use App\Http\Services\Patient\PatientFiltros;
use App\Models\Siprov;
use App\Models\TelemedicinaTenant;
use App\Models\Tenant;
use App\Models\TenantPlanoBeneficiario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientFiltrosTest extends TestCase
{
    use RefreshDatabase;

    public function test_mapa_de_planos_usa_a_siprov_so_para_quem_nao_tem_vinculo_no_parceiro(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::create(['id' => 'tenant-filtros']));

        TelemedicinaTenant::create(['tenant_id' => $tenant->id, 'data' => ['siprov_id' => 1, 'cpf_cnpj' => '11111111111', 'cod_planos' => ['331385']]]);
        TenantPlanoBeneficiario::create(['tenant_id' => $tenant->id, 'cod_plano' => '900001', 'nome' => 'Interno', 'cpf' => '22222222222']);

        // Na SIPROV: um com vínculo no parceiro (vínculo vence) e um sem vínculo (CPF com máscara).
        $this->siprov('11111111111', 331384);
        $this->siprov('081.711.883-70', 331385);

        $mapa = PatientFiltros::codigosPorCpf($tenant->id, ['111.111.111-11', '222.222.222-22', '081.711.883-70', '99999999999']);

        $this->assertSame(['331385'], $mapa['11111111111']);
        $this->assertSame(['900001'], $mapa['22222222222']);
        $this->assertSame(['331385'], $mapa['08171188370']);
        $this->assertArrayNotHasKey('99999999999', $mapa);

        // Sem os CPFs dos pacientes, a SIPROV não entra.
        $this->assertArrayNotHasKey('08171188370', PatientFiltros::codigosPorCpf($tenant->id));
    }

    private function siprov(string $cpf, int $codPlano): void
    {
        Siprov::create([
            'codigo_integracao' => 'USR-'.preg_replace('/\D/', '', $cpf),
            'nome_pessoa' => 'Associado',
            'cpf_cnpj' => $cpf,
            'cod_plano' => $codPlano,
            'status' => Siprov::STATUS_SUCCESS,
            'integrated_at' => now(),
        ]);
    }
}
