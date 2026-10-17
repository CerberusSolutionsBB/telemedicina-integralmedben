<?php

namespace Tests\Feature;

use App\Http\Requests\CadastroIconeRequest;
use App\Http\Requests\CupomBeneficioRequest;
use App\Models\Audit;
use App\Models\Convenio;
use App\Models\CupomBeneficio;
use App\Models\Servico;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenant\CadastroIconeService;
use App\Services\Tenant\CupomBeneficioPdfService;
use App\Support\IconesLucide;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Cadastros do parceiro com título, descrição, ícone e status (serviços e convênios).
 */
class CadastroIconeTest extends TestCase
{
    use RefreshDatabase {
        RefreshDatabase::migrateFreshUsing as migrateFreshPadrao;
    }

    private const MIGRATIONS = [
        'database/migrations/tenant/2026_10_16_220853_create_servicos_table.php',
        'database/migrations/tenant/2026_10_16_223027_create_convenios_table.php',
        'database/migrations/tenant/2026_10_16_224720_create_cupom_beneficios_table.php',
        'database/migrations/tenant/2026_10_16_232140_add_codigo_to_cupom_beneficios_table.php',
    ];

    // As tabelas ficam nas migrations do tenant: recria o banco de teste incluindo-as
    // (DDL no setUp confirmaria a transação do teste no MySQL).
    protected function beforeRefreshingDatabase()
    {
        if (RefreshDatabaseState::$migrated && ! Schema::hasColumn('cupom_beneficios', 'codigo')) {
            RefreshDatabaseState::$migrated = false;
        }
    }

    protected function migrateFreshUsing()
    {
        return [...$this->migrateFreshPadrao(), '--path' => ['database/migrations', ...self::MIGRATIONS]];
    }

    public function test_validacao(): void
    {
        $regras = (new CadastroIconeRequest)->rules();

        $this->assertTrue(Validator::make(['titulo' => 'Teleconsulta', 'icone' => 'Video', 'status' => true], $regras)->passes());
        // Qualquer ícone do lucide, não só os sugeridos.
        $this->assertTrue(Validator::make(['titulo' => 'Agenda', 'icone' => 'CalendarCheck2', 'status' => true], $regras)->passes());

        $erros = Validator::make([
            'titulo' => str_repeat('a', CadastroIconeRequest::LIMITES['titulo'] + 1),
            'icone' => '<svg onload=alert(1)>',
            'status' => 'talvez',
        ], $regras)->errors();

        $this->assertTrue($erros->has('titulo'));
        $this->assertTrue($erros->has('icone'));
        $this->assertTrue($erros->has('status'));
        $this->assertTrue(Validator::make(['icone' => 'Video', 'status' => true], $regras)->errors()->has('titulo'));
    }

    public function test_rotulo_do_icone(): void
    {
        // Sugerido tem rótulo em português; os demais ícones viram o nome separado.
        $this->assertSame('Coração', IconesLucide::rotulo('HeartPulse'));
        $this->assertSame('Calendar Check2', IconesLucide::rotulo('CalendarCheck2'));
    }

    /**
     * @param  class-string<Servico|Convenio>  $model
     */
    #[DataProvider('modelos')]
    public function test_historico_registra_criacao_alteracao_e_exclusao(string $model): void
    {
        config(['audit.console' => true]);
        $this->actingAs($usuario = User::factory()->create(['name' => 'Ana']));

        $item = $model::create(['titulo' => 'Original', 'descricao' => 'Descrição', 'icone' => 'Video', 'status' => true]);
        $item->update(['titulo' => 'Novo título', 'status' => false]);

        // Fora do tenant não há tag: simula a tag que o tenant grava.
        Audit::query()->update(['tags' => 'tenant:parceiro-x']);

        $historico = app(CadastroIconeService::class)->historico($item, 'parceiro-x');

        $this->assertSame(['Alterado', 'Criado'], array_column($historico, 'evento'));
        $this->assertSame('Ana', $historico[0]['usuario']);
        $this->assertSame(
            [['campo' => 'Título', 'antes' => 'Original', 'depois' => 'Novo título'], ['campo' => 'Status', 'antes' => 'Ativo', 'depois' => 'Inativo']],
            $historico[0]['alteracoes'],
        );
        $this->assertContains(['campo' => 'Ícone', 'antes' => null, 'depois' => 'Teleconsulta'], $historico[1]['alteracoes']);

        // Auditoria de outro tenant não aparece.
        $this->assertSame([], app(CadastroIconeService::class)->historico($item, 'outro-parceiro'));

        $item->delete();
        $ultima = Audit::where('auditable_type', $model)->latest('id')->first();
        $this->assertSame(['deleted', $usuario->id], [$ultima->event, $ultima->user_id]);
    }

    public function test_regras_do_cupom_de_beneficio(): void
    {
        $convenio = Convenio::create(['titulo' => 'Farmácia', 'icone' => 'Pill', 'status' => true]);
        $base = ['titulo' => '15% na farmácia', 'icone' => 'Pill', 'status' => true, 'tipo_desconto' => 'percentual', 'valor_desconto' => 15];

        $validar = fn (array $dados) => Validator::make($dados, CupomBeneficioRequest::createFrom(request()->merge($dados))->rules());

        $this->assertTrue($validar([...$base, 'convenio_id' => $convenio->id])->passes());
        // Precisa de convênio ou serviço.
        $this->assertTrue($validar($base)->errors()->has('convenio_id'));
        // Convênio inexistente, percentual acima de 100 e plano fora do catálogo.
        $erros = $validar([...$base, 'convenio_id' => 999, 'valor_desconto' => 150, 'cod_plano' => 'nao-existe'])->errors();
        $this->assertTrue($erros->has('convenio_id'));
        $this->assertTrue($erros->has('valor_desconto'));
        $this->assertTrue($erros->has('cod_plano'));
        // Valor fixo pode passar de 100.
        $this->assertTrue($validar([...$base, 'convenio_id' => $convenio->id, 'tipo_desconto' => 'valor_fixo', 'valor_desconto' => 150])->passes());
    }

    public function test_codigo_do_cupom_gerado_normalizado_e_unico(): void
    {
        $convenio = Convenio::create(['titulo' => 'Farmácia', 'icone' => 'Pill', 'status' => true]);
        $dados = ['titulo' => 'Cupom', 'icone' => 'Pill', 'status' => true, 'convenio_id' => $convenio->id, 'tipo_desconto' => 'percentual', 'valor_desconto' => 10];

        // Sem código: gera 8 caracteres sem os que se confundem.
        $gerado = CupomBeneficio::create($dados)->codigo;
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{8}$/', $gerado);

        // Código próprio: maiúsculas e sem espaços.
        $this->assertSame('FARMACIA15', CupomBeneficio::create([...$dados, 'codigo' => 'farmacia 15'])->codigo);

        $validar = function (array $extra, ?int $id = null) use ($dados) {
            $request = CupomBeneficioRequest::create('/', 'POST', [...$dados, ...$extra]);
            $rota = (new Route('POST', '/', []))->bind($request);
            $rota->setParameter('id', $id);
            $request->setRouteResolver(fn () => $rota);
            (fn () => $this->prepareForValidation())->call($request);

            return Validator::make($request->all(), $request->rules(), $request->messages())->errors();
        };

        $this->assertTrue($validar(['codigo' => 'Farmacia15'])->has('codigo'), 'Repetido (depois de normalizar) é recusado.');
        $this->assertTrue($validar(['codigo' => 'CÓD!'])->has('codigo'), 'Acento e símbolo são recusados.');
        $this->assertFalse($validar(['codigo' => ''])->has('codigo'), 'Na criação, vazio = gerar.');
        $this->assertTrue($validar(['codigo' => ''], 99)->has('codigo'), 'Na edição, o código é obrigatório.');
        $this->assertFalse($validar(['codigo' => 'FARMACIA15'], CupomBeneficio::where('codigo', 'FARMACIA15')->value('id'))->has('codigo'), 'O próprio cupom pode manter o código.');
    }

    public function test_pdf_do_cupom_de_beneficio(): void
    {
        Carbon::setTestNow('2026-10-16 12:00:00');
        Tenant::withoutEvents(fn () => Tenant::create(['id' => 'parceiro-pdf', 'name' => 'Clínica Vida']));
        $convenio = Convenio::create(['titulo' => 'Farmácia Bem Estar', 'icone' => 'Pill', 'status' => true]);
        $cupom = CupomBeneficio::create([
            'codigo' => 'FARMA15', 'titulo' => '15% na farmácia', 'icone' => 'Pill', 'status' => true,
            'descricao' => "Apresente o cartão no caixa.\nNão vale para manipulados.",
            'convenio_id' => $convenio->id, 'cod_plano' => '331385', 'tipo_desconto' => 'percentual', 'valor_desconto' => 15, 'prazo' => '2026-12-31',
        ]);

        $dados = app(CupomBeneficioPdfService::class)->dados($cupom, 'parceiro-pdf');

        $this->assertSame(['Clínica Vida', 'Cupom de benefício', 'FARMA15', '15% OFF', 'em Farmácia Bem Estar', 'Válido até 31/12/2026'],
            [$dados['rotulo'], $dados['titulo'], $dados['codigo'], $dados['desconto'], $dados['textoDesconto'], $dados['validade']]);
        $this->assertSame(['Válido para beneficiários do plano Clínica Familiar.', 'Apresente o cartão no caixa.', 'Não vale para manipulados.'], $dados['condicoes']);
        $this->assertStringContainsString(':codigo em Farmácia Bem Estar', $dados['comoUsar'][0]);
        $this->assertNull($dados['aviso']);

        $cupom->update(['prazo' => '2026-10-01']);
        $this->assertStringContainsString('venceu em 01/10/2026', app(CupomBeneficioPdfService::class)->dados($cupom->fresh(), 'parceiro-pdf')['aviso']);
    }

    public function test_cupom_vencido_e_desconto_formatado(): void
    {
        Carbon::setTestNow('2026-10-16 15:00:00');

        $cupom = new CupomBeneficio(['tipo_desconto' => 'percentual', 'valor_desconto' => 15, 'prazo' => '2026-10-16']);
        $this->assertFalse($cupom->vencido(), 'O dia do prazo ainda vale.');
        $this->assertSame('15%', $cupom->descontoFormatado());

        $cupom->fill(['tipo_desconto' => 'valor_fixo', 'valor_desconto' => 30, 'prazo' => '2026-10-15']);
        $this->assertTrue($cupom->vencido());
        $this->assertSame('R$ 30,00', $cupom->descontoFormatado());

        $this->assertFalse((new CupomBeneficio(['prazo' => null]))->vencido(), 'Sem prazo não vence.');
    }

    public function test_historico_com_campos_extras_e_convenio_em_uso_nao_e_excluido(): void
    {
        config(['audit.console' => true]);
        $convenio = Convenio::create(['titulo' => 'Farmácia', 'icone' => 'Pill', 'status' => true]);
        $cupom = CupomBeneficio::create([
            'titulo' => 'Desconto', 'icone' => 'Pill', 'status' => true, 'convenio_id' => $convenio->id,
            'tipo_desconto' => 'percentual', 'valor_desconto' => 10,
        ]);
        $cupom->update(['valor_desconto' => 20, 'prazo' => '2026-12-31']);
        Audit::query()->update(['tags' => 'tenant:parceiro-x']);

        $historico = app(CadastroIconeService::class)->historico(
            $cupom,
            'parceiro-x',
            ['valor_desconto' => 'Desconto', 'prazo' => 'Prazo'],
            fn (string $campo, mixed $valor) => match ($campo) {
                'prazo' => Carbon::parse($valor)->format('d/m/Y'),
                'valor_desconto' => number_format((float) $valor, 2, ',', '.'),
                default => null,
            },
        );

        $this->assertContains(['campo' => 'Prazo', 'antes' => null, 'depois' => '31/12/2026'], $historico[0]['alteracoes']);
        $this->assertContains(['campo' => 'Desconto', 'antes' => '10,00', 'depois' => '20,00'], $historico[0]['alteracoes']);

        // Convênio usado por um cupom: a chave estrangeira impede a exclusão.
        $this->expectException(QueryException::class);
        $convenio->delete();
    }

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function modelos(): array
    {
        return [
            'serviço' => [Servico::class],
            'convênio' => [Convenio::class],
            'cupom de benefício' => [CupomBeneficio::class],
        ];
    }
}
