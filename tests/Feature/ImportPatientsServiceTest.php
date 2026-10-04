<?php

namespace Tests\Feature;

use App\Http\Services\Patient\ImportPatientsService;
use App\Http\Services\Patient\ModeloImportacaoPacientesService;
use App\Models\Patient;
use App\Models\PatientAnswer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Tests\TestCase;

class ImportPatientsServiceTest extends TestCase
{
    use RefreshDatabase;

    private $perguntas;

    protected function setUp(): void
    {
        parent::setUp();

        // Tabelas do banco do tenant, criadas no banco de teste (sem tenancy).
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('central_patient_id')->nullable();
            $table->string('nome')->nullable();
            $table->string('cpf')->nullable();
            $table->string('rg')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('sexo')->nullable();
            $table->string('email')->nullable();
            $table->string('numero')->nullable();
            $table->boolean('status')->default(true);
            $table->string('status_registro')->nullable();
            $table->json('enderecos')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('patient_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('question_id');
            $table->text('answer')->nullable();
            $table->timestamps();
        });

        $this->perguntas = collect([(object) ['id' => 7, 'title' => 'Convênio anterior']]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('patient_answers');
        Schema::dropIfExists('patients');

        parent::tearDown();
    }

    private function modelo(): string
    {
        $resposta = app(ModeloImportacaoPacientesService::class)->gerar($this->perguntas);

        ob_start();
        $resposta->sendContent();
        $caminho = tempnam(sys_get_temp_dir(), 'modelo').'.xlsx';
        file_put_contents($caminho, ob_get_clean());

        return $caminho;
    }

    public function test_modelo_tem_cabecalho_listas_e_instrucoes(): void
    {
        $planilha = IOFactory::load($this->modelo());

        $this->assertSame(['Beneficiários', 'Instruções'], $planilha->getSheetNames());

        $dados = $planilha->getSheet(0);
        $this->assertSame(
            ['Nome *', 'Data de Nascimento *', 'CPF', 'RG', 'Sexo', 'Email', 'Telefone', 'Status', 'Convênio anterior'],
            $dados->rangeToArray('A1:I1')[0],
        );
        $this->assertSame('', (string) $dados->getCell('A2')->getValue(), 'modelo não traz linha de exemplo');
        $this->assertSame('"Masculino,Feminino"', $dados->getCell('E50')->getDataValidation()->getFormula1());
        $this->assertSame('A2', $dados->getFreezePane());
    }

    public function test_importa_o_modelo_preenchido_e_relata_linhas_com_erro(): void
    {
        Patient::create(['nome' => 'Já existe', 'cpf' => '111.444.777-35']);

        $planilha = IOFactory::load($this->modelo());
        $dados = $planilha->getSheet(0);
        $dados->fromArray([
            ['Maria da Silva', ExcelDate::PHPToExcel(new \DateTime('1990-03-25')), '12345678909', '', 'Feminino', 'maria@exemplo.com', '11999990000', '', 'Unimed'],
            ['João Souza', '05/11/1985', '987.654.321-00', '', 'masculino', '', '', 'Inativo', ''],
            [],
            ['', '01/01/2000', '', '', '', '', '', '', ''],
            ['Duplicado', '01/01/2000', '11144477735', '', '', '', '', '', ''],
            ['Sexo errado', '01/01/2000', '', '', 'Outro', '', '', '', ''],
            ['Sem data', '', '', '', '', '', '', '', ''],
        ], null, 'A2');

        $caminho = tempnam(sys_get_temp_dir(), 'preenchido').'.xlsx';
        IOFactory::createWriter($planilha, 'Xlsx')->save($caminho);
        $arquivo = new UploadedFile($caminho, 'beneficiarios.xlsx', null, null, true);

        $resultado = app(ImportPatientsService::class)->execute($arquivo, $this->perguntas);

        $this->assertSame(2, $resultado['imported']);
        $this->assertCount(4, $resultado['errors']);
        $this->assertStringStartsWith('Linha 5:', $resultado['errors'][0]);
        $this->assertStringContainsString('já cadastrado', $resultado['errors'][1]);

        $maria = Patient::where('nome', 'Maria da Silva')->first();
        $this->assertSame('123.456.789-09', $maria->cpf);
        $this->assertSame('1990-03-25', $maria->data_nascimento->toDateString());
        $this->assertSame('feminino', $maria->sexo->value);
        $this->assertSame('(11) 99999-0000', $maria->numero);
        $this->assertTrue($maria->status);
        $this->assertSame('Unimed', PatientAnswer::where('patient_id', $maria->id)->where('question_id', 7)->value('answer'));

        $joao = Patient::where('nome', 'João Souza')->first();
        $this->assertSame('1985-11-05', $joao->data_nascimento->toDateString());
        $this->assertFalse($joao->status);
    }

    public function test_arquivo_sem_coluna_nome_e_recusado(): void
    {
        $caminho = tempnam(sys_get_temp_dir(), 'errado').'.xlsx';
        $planilha = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $planilha->getActiveSheet()->fromArray([['Qualquer'], ['valor']]);
        IOFactory::createWriter($planilha, 'Xlsx')->save($caminho);

        $resultado = app(ImportPatientsService::class)->execute(new UploadedFile($caminho, 'x.xlsx', null, null, true), $this->perguntas);

        $this->assertSame(0, $resultado['imported']);
        $this->assertStringContainsString('Coluna "Nome"', $resultado['errors'][0]);
    }
}
