<?php

namespace Tests\Feature;

use App\Http\Requests\DesempenhoRequest;
use App\Models\Audit;
use App\Models\Desempenho;
use App\Models\TenantPlano;
use App\Models\User;
use App\Services\Tenant\DesempenhoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DesempenhoServiceTest extends TestCase
{
    use RefreshDatabase {
        RefreshDatabase::migrateFreshUsing as migrateFreshPadrao;
    }

    private DesempenhoService $service;

    private User $joao;

    private User $maria;

    private User $outro;

    private Role $atendente;

    private const MIGRATION = 'database/migrations/tenant/2026_10_01_000000_create_desempenhos_table.php';

    private const MIGRATION_USUARIOS = 'database/migrations/tenant/2026_10_07_000000_create_desempenho_user_table.php';

    private const MIGRATION_META_VALOR = 'database/migrations/tenant/2026_10_07_010000_add_meta_valor_to_desempenhos_table.php';

    // As tabelas do módulo ficam nas migrations do tenant. Em vez de migrar no
    // setUp (DDL confirma a transação do teste no MySQL), recria o banco uma vez
    // já incluindo essas migrations.
    protected function beforeRefreshingDatabase()
    {
        if (RefreshDatabaseState::$migrated && ! Schema::hasTable('desempenhos')) {
            RefreshDatabaseState::$migrated = false;
        }
    }

    protected function migrateFreshUsing()
    {
        return [...$this->migrateFreshPadrao(), '--path' => ['database/migrations', self::MIGRATION, self::MIGRATION_USUARIOS, self::MIGRATION_META_VALOR]];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15 12:00:00');
        $this->service = app(DesempenhoService::class);

        $this->atendente = Role::create(['name' => 'Atendente', 'guard_name' => 'web']);
        Role::create(['name' => 'Financeiro', 'guard_name' => 'web']);

        $this->joao = User::factory()->create(['name' => 'João'])->assignRole('Atendente');
        $this->maria = User::factory()->create(['name' => 'Maria'])->assignRole('Atendente');
        $this->outro = User::factory()->create(['name' => 'Outro'])->assignRole('Financeiro');

        // João: 3 no Familiar + 1 no Individual (fora do período); Maria: 1 no Familiar; Outro: 5 (fora dos perfis).
        $this->registro($this->joao, '331385', '2026-10-02');
        $this->registro($this->joao, '331385', '2026-10-05');
        $this->registro($this->joao, '331385', '2026-10-10');
        $this->registro($this->joao, '331384', '2026-09-20');
        $this->registro($this->maria, '331385', '2026-10-03');
        foreach (range(1, 5) as $i) {
            $this->registro($this->outro, '331385', '2026-10-04');
        }
        $this->registro($this->maria, '331385', '2026-10-03', 'outro-tenant');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_individual_conta_por_usuario_no_plano_e_periodo(): void
    {
        $progresso = $this->progresso(['tipo_meta' => 'individual', 'meta' => 3, 'escopo_plano' => 'plano', 'cod_plano' => '331385']);

        $this->assertSame(['João' => 3, 'Maria' => 1], collect($progresso['usuarios'])->pluck('total', 'nome')->all());
        $this->assertSame([true, false], array_column($progresso['usuarios'], 'atingiu'));
        $this->assertSame(1, $progresso['atingiram']);
        $this->assertSame(2, $progresso['participantes']);
        $this->assertSame(50, $progresso['percentual']);
        $this->assertSame('em_andamento', $progresso['status']);
    }

    public function test_coletiva_soma_o_grupo_e_fica_atingida(): void
    {
        $progresso = $this->progresso(['tipo_meta' => 'coletiva', 'meta' => 4, 'escopo_plano' => 'todos']);

        $this->assertSame(4, $progresso['total']);
        $this->assertSame(100, $progresso['percentual']);
        $this->assertSame('atingida', $progresso['status']);
        $this->assertNull($progresso['atingiram']);
    }

    public function test_todos_os_planos_inclui_outros_planos_no_periodo(): void
    {
        $progresso = $this->progresso(['tipo_meta' => 'coletiva', 'meta' => 10, 'escopo_plano' => 'todos', 'data_inicio' => '2026-09-01']);

        $this->assertSame(5, $progresso['total']); // inclui o Individual de 20/09
    }

    public function test_status_nao_iniciada_e_encerrada(): void
    {
        $this->assertSame('nao_iniciada', $this->progresso(['meta' => 99, 'data_inicio' => '2026-11-01', 'prazo' => '2026-11-30'])['status']);
        $this->assertSame('encerrada', $this->progresso(['meta' => 99, 'data_inicio' => '2026-10-01', 'prazo' => '2026-10-10'])['status']);
    }

    public function test_validacao_exige_perfil_plano_quando_escopo_plano_e_prazo_apos_inicio(): void
    {
        $request = new DesempenhoRequest;
        $erros = Validator::make([
            'titulo' => 'Meta', 'roles' => [], 'funcao' => 'registro_beneficiario_plano',
            'escopo_plano' => 'plano', 'cod_plano' => null, 'tipo_meta' => 'individual',
            'meta' => 0, 'data_inicio' => '2026-10-10', 'prazo' => '2026-10-01',
        ], $request->rules(), $request->messages())->errors();

        foreach (['roles', 'cod_plano', 'meta', 'prazo'] as $campo) {
            $this->assertTrue($erros->has($campo), "esperado erro em {$campo}");
        }
    }

    public function test_limites_de_caracteres_do_titulo_e_descricao(): void
    {
        $request = new DesempenhoRequest;
        $base = [
            'roles' => [$this->atendente->id], 'funcao' => 'registro_beneficiario_plano', 'escopo_plano' => 'todos',
            'tipo_meta' => 'individual', 'meta' => 1, 'data_inicio' => '2026-10-01', 'prazo' => '2026-10-31',
        ];
        $valida = fn (array $dados) => Validator::make([...$base, ...$dados], $request->rules(), $request->messages())->errors();

        $this->assertFalse($valida([
            'titulo' => str_repeat('a', Desempenho::LIMITES['titulo']),
            'descricao' => str_repeat('b', Desempenho::LIMITES['descricao']),
        ])->any());

        $erros = $valida([
            'titulo' => str_repeat('a', Desempenho::LIMITES['titulo'] + 1),
            'descricao' => str_repeat('b', Desempenho::LIMITES['descricao'] + 1),
        ]);
        $this->assertSame('O título pode ter no máximo 255 caracteres.', $erros->first('titulo'));
        $this->assertSame('A descrição pode ter no máximo 2000 caracteres.', $erros->first('descricao'));
    }

    public function test_registros_por_plano(): void
    {
        $todos = $this->progresso(['escopo_plano' => 'todos', 'data_inicio' => '2026-09-01']);
        $porPlano = collect($todos['por_plano'])->pluck('total', 'cod_plano')->all();
        $this->assertSame(['331385' => 4, '331384' => 1, '331386' => 0, 'beneficios' => 0], $porPlano);
        $this->assertSame($todos['total'], array_sum($porPlano));

        // Meta de um plano: só o card desse plano.
        $umPlano = $this->progresso(['escopo_plano' => 'plano', 'cod_plano' => '331385']);
        $this->assertSame([['cod_plano' => '331385', 'plano' => 'Clínica Familiar', 'total' => 4]], $umPlano['por_plano']);
    }

    public function test_somente_usuarios_selecionados_participam_da_meta(): void
    {
        $progresso = $this->progresso(
            ['tipo_meta' => 'individual', 'meta' => 3, 'escopo_plano' => 'plano', 'cod_plano' => '331385'],
            [$this->joao->id],
        );

        $this->assertSame(['João' => 3], collect($progresso['usuarios'])->pluck('total', 'nome')->all());
        $this->assertSame(1, $progresso['participantes']);
        $this->assertSame(100, $progresso['percentual']);
        $this->assertSame('atingida', $progresso['status']);
    }

    public function test_validacao_de_usuarios_exige_vinculo_com_os_perfis(): void
    {
        $request = new DesempenhoRequest;
        $base = [
            'titulo' => 'Meta', 'roles' => [$this->atendente->id], 'funcao' => 'registro_beneficiario_plano',
            'escopo_plano' => 'todos', 'tipo_meta' => 'individual', 'meta' => 1,
            'data_inicio' => '2026-10-01', 'prazo' => '2026-10-31',
        ];

        $semVinculo = Validator::make([...$base, 'usuarios' => [$this->outro->id]], $request->rules(), $request->messages());
        $this->assertTrue($semVinculo->errors()->has('usuarios.0'));

        $comVinculo = Validator::make([...$base, 'usuarios' => [$this->joao->id]], $request->rules(), $request->messages());
        $this->assertFalse($comVinculo->errors()->any());
    }

    public function test_comissao_soma_vendas_e_valor_por_vendedor(): void
    {
        // João: 2 vendas (R$ 100). Maria: 1 venda (R$ 30) — novembro isola dos registros do setUp.
        $this->registroComissao($this->joao, '331385', '2026-11-02', 50.0);
        $this->registroComissao($this->joao, '331384', '2026-11-05', 50.0);
        $this->registroComissao($this->maria, '331385', '2026-11-03', 30.0);

        $progresso = $this->progresso(
            [
                'funcao' => Desempenho::FUNCAO_COMISSAO_VENDA_PLANO,
                'tipo_meta' => 'individual',
                'meta' => 2,
                'meta_valor' => 100,
                'data_inicio' => '2026-11-01',
                'prazo' => '2026-11-30',
            ],
            [$this->joao->id, $this->maria->id],
        );

        $this->assertSame(3, $progresso['total']);
        $this->assertSame(130.0, $progresso['valor']);
        $this->assertSame(1, $progresso['atingiram']);
        $this->assertSame(1, $progresso['atingiram_valor']);
        $this->assertSame(50, $progresso['percentual']);
        $this->assertSame(50, $progresso['percentual_valor']);
        $this->assertSame('nao_iniciada', $progresso['status']);

        $porUsuario = collect($progresso['usuarios'])->keyBy('nome');
        $this->assertSame(2, $porUsuario['João']['total']);
        $this->assertSame(100.0, $porUsuario['João']['valor']);
        $this->assertTrue($porUsuario['João']['atingiu']);
        $this->assertTrue($porUsuario['João']['atingiu_valor']);
        $this->assertSame(1, $porUsuario['Maria']['total']);
        $this->assertSame(30.0, $porUsuario['Maria']['valor']);
        $this->assertFalse($porUsuario['Maria']['atingiu']);
        $this->assertFalse($porUsuario['Maria']['atingiu_valor']);

        $porPlano = collect($progresso['por_plano'])->keyBy('cod_plano');
        $this->assertSame(1, $porPlano['331384']['total']);
        $this->assertSame(2, $porPlano['331385']['total']);
        $this->assertSame(80.0, $porPlano['331385']['valor']);
    }

    public function test_comissao_usa_regra_do_plano_quando_registro_nao_tem_snapshot(): void
    {
        // Linha do tenant sem disparar a criação de banco do stancl/tenancy.
        DB::table('tenants')->insert([
            'id' => 'meu-tenant',
            'data' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TenantPlano::create([
            'tenant_id' => 'meu-tenant',
            'cod_plano' => '331385',
            'quantidade' => 10,
            'saldo' => 10,
            'valor' => 200,
            'comissao_tipo' => TenantPlano::COMISSAO_PERCENTUAL,
            'comissao_valor' => 10,
        ]);

        $this->registro($this->joao, '331385', '2026-11-02'); // sem snapshot de comissão

        $progresso = $this->progresso(
            [
                'funcao' => Desempenho::FUNCAO_COMISSAO_VENDA_PLANO,
                'meta' => 1,
                'meta_valor' => 20,
                'data_inicio' => '2026-11-01',
                'prazo' => '2026-11-30',
            ],
            [$this->joao->id],
        );

        $this->assertSame(1, $progresso['total']);
        $this->assertSame(20.0, $progresso['valor']); // 200 × 10%
    }

    private function progresso(array $atributos, ?array $usuarios = null): array
    {
        $desempenho = Desempenho::create([
            'titulo' => 'Meta',
            'funcao' => Desempenho::FUNCAO_REGISTRO_BENEFICIARIO_PLANO,
            'escopo_plano' => 'todos',
            'tipo_meta' => 'individual',
            'meta' => 3,
            'data_inicio' => '2026-10-01',
            'prazo' => '2026-10-31',
            ...$atributos,
        ]);
        $desempenho->roles()->sync([$this->atendente->id]);
        $desempenho->usuarios()->sync($usuarios ?? [$this->joao->id, $this->maria->id]);

        return $this->service->progresso($desempenho->fresh(), 'meu-tenant');
    }

    private function registro(User $user, string $codPlano, string $data, string $tenant = 'meu-tenant'): void
    {
        $audit = Audit::create([
            'event' => 'registro_plano',
            'auditable_type' => 'App\\Models\\TelemedicinaTenant',
            'auditable_id' => 1,
            'user_type' => User::class,
            'user_id' => $user->id,
            'old_values' => [],
            'new_values' => ['cod_plano' => $codPlano, 'usuario' => $user->name],
            'tags' => "tenant:{$tenant}",
        ]);
        $audit->forceFill(['created_at' => "{$data} 10:00:00"])->save();
    }

    /**
     * Registro de venda já com o snapshot de comissão e o vendedor.
     */
    private function registroComissao(User $user, string $codPlano, string $data, float $comissao): void
    {
        $audit = Audit::create([
            'event' => 'registro_plano',
            'auditable_type' => 'App\\Models\\TelemedicinaTenant',
            'auditable_id' => 1,
            'user_type' => User::class,
            'user_id' => $user->id,
            'old_values' => [],
            'new_values' => [
                'cod_plano' => $codPlano,
                'plano' => 'Plano '.$codPlano,
                'valor' => 100,
                'comissao' => $comissao,
                'vendedor_id' => $user->id,
                'vendedor' => $user->name,
                'usuario' => $user->name,
            ],
            'tags' => 'tenant:meu-tenant',
        ]);
        $audit->forceFill(['created_at' => "{$data} 10:00:00"])->save();
    }
}
