<?php

namespace App\Http\Services\Patient;

use App\Models\Audit;
use App\Models\Patient;
use App\Models\User;
use App\Services\Tenant\PacientePlanoService;
use App\Support\PatientAnswerFormatter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportPatientsService
{
    private const ORIGENS = PatientsReportPdfService::ORIGENS;

    /** Início dos títulos do resumo final; a importação para ao encontrá-lo. */
    public const PREFIXO_RESUMO = 'Quantitativo por ';

    public function __construct(
        private readonly PacientePlanoService $pacientePlanoService,
        private readonly ModeloImportacaoPacientesService $modeloImportacao,
    ) {}

    /**
     * Exporta os beneficiários com os mesmos filtros da listagem (PatientFiltros).
     */
    public function execute(string $format = 'csv', array $filtros = []): StreamedResponse
    {
        $tenantId = (string) tenant('id');

        $patients = PatientFiltros::aplicar(Patient::with('answers.question'), $tenantId, $filtros)->latest()->get();
        $questions = $patients->flatMap(fn ($p) => $p->answers->pluck('question'))->unique('id')->values();

        $extras = [
            'planos' => $this->pacientePlanoService->planosPorCpf($tenantId, $patients->pluck('cpf')->all()),
            'criadores' => $this->criadores($tenantId, $patients->pluck('id')->all()),
        ];

        $rows = $this->buildRows($patients, $questions, $extras);
        [$rows, $titulos] = $this->comResumo($rows);
        $arquivo = 'beneficiarios-'.now('America/Sao_Paulo')->format('Y-m-d');

        if ($format === 'xlsx') {
            return $this->writeXlsx($rows, $arquivo.'.xlsx', $titulos);
        }

        $response = $this->writeCsv($rows);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$arquivo.'.csv"');

        return $response;
    }

    /**
     * Paciente => nome de quem criou (auditoria "created" do tenant).
     *
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    private function criadores(string $tenantId, array $ids): array
    {
        $porPaciente = Audit::where('auditable_type', Patient::class)
            ->where('event', 'created')
            ->where('tags', 'tenant:'.$tenantId)
            ->whereIn('auditable_id', $ids)
            ->whereNotNull('user_id')
            ->pluck('user_id', 'auditable_id');

        $nomes = User::whereIn('id', $porPaciente->unique())->pluck('name', 'id');

        return $porPaciente->map(fn ($userId) => $nomes[$userId] ?? '')->filter()->all();
    }

    public function generateTemplate($questions, string $format = 'csv'): StreamedResponse
    {
        if ($format === 'xlsx') {
            return $this->modeloImportacao->gerar($questions);
        }

        return $this->templateCsv($questions);
    }

    private function headers(): array
    {
        return [
            'Nome', 'CPF', 'RG', 'Data de Nascimento', 'Sexo',
            'Email', 'Telefone', 'Status',
            'ID', 'Data de Cadastro',
        ];
    }

    private function templateRows($questions): array
    {
        // Só o cabeçalho: uma linha de exemplo seria importada como beneficiário.
        return [array_merge($this->headers(), $questions->pluck('title')->toArray())];
    }

    /**
     * Colunas da exportação: as do modelo de importação + plano, origem e quem criou.
     * A importação ignora as colunas que não conhece, então o arquivo pode ser reimportado.
     */
    private function exportHeaders(): array
    {
        return [...$this->headers(), 'Plano', 'Origem do Registro', 'Criado por'];
    }

    private function buildRows($patients, $questions, array $extras): array
    {
        $headers = array_merge($this->exportHeaders(), $questions->pluck('title')->toArray());

        $rows = [$headers];

        foreach ($patients as $patient) {
            $origem = $patient->status_registro?->value ?? $patient->status_registro;

            $row = [
                $patient->nome ?? '',
                PatientAnswerFormatter::maskCpf($patient->cpf),
                $patient->rg ?? '',
                $patient->data_nascimento?->format('d/m/Y') ?? '',
                $patient->sexo?->label() ?? '',
                $patient->email ?? '',
                PatientAnswerFormatter::maskTelefone($patient->numero),
                $patient->status ? 'Ativo' : 'Inativo',
                $patient->id,
                $patient->created_at?->format('d/m/Y H:i') ?? '',
                $extras['planos'][preg_replace('/\D/', '', (string) $patient->cpf)] ?? 'Sem plano',
                self::ORIGENS[$origem] ?? '',
                $extras['criadores'][$patient->id] ?? 'Não registrado',
            ];

            $answers = $patient->answers->keyBy('question_id');
            foreach ($questions as $question) {
                $row[] = PatientAnswerFormatter::formatAnswer($answers->get($question->id)?->answer, $question);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Ao final da tabela: quantitativos dos beneficiários exportados por quem
     * criou, status, plano e tipo de registro (quantidade e percentual).
     *
     * @return array{0: array, 1: array<int, int>} linhas e números (1-based) das linhas de título
     */
    private function comResumo(array $rows): array
    {
        $linhas = collect(array_slice($rows, 1));
        $total = $linhas->count();
        $colunas = array_flip($this->exportHeaders());

        $grupos = [
            self::PREFIXO_RESUMO.'criador (usuário)' => $linhas->countBy(fn ($l) => $l[$colunas['Criado por']])->sortDesc(),
            self::PREFIXO_RESUMO.'status' => collect(['Ativo' => 0, 'Inativo' => 0])
                ->merge($linhas->countBy(fn ($l) => $l[$colunas['Status']])),
            self::PREFIXO_RESUMO.'plano' => $linhas->countBy(fn ($l) => $l[$colunas['Plano']])->sortDesc(),
            self::PREFIXO_RESUMO.'tipo de registro' => $linhas->countBy(fn ($l) => $l[$colunas['Origem do Registro']] ?: 'Não informado')->sortDesc(),
        ];

        $titulos = [1];
        $percentual = fn (int $n) => $total ? number_format($n * 100 / $total, 1, ',', '.').'%' : '0%';

        foreach ($grupos as $titulo => $contagem) {
            $rows[] = [];
            $rows[] = [$titulo, 'Quantidade', '%'];
            $titulos[] = count($rows);

            foreach ($contagem as $rotulo => $quantidade) {
                $rows[] = [$rotulo, $quantidade, $percentual($quantidade)];
            }

            $rows[] = ['Total', $total, $total ? '100%' : '0%'];
        }

        return [$rows, $titulos];
    }

    private function writeCsv(array $rows): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                fputcsv($handle, $row, escape: '\\');
            }

            fclose($handle);
        });

        return $response;
    }

    private function templateCsv($questions): StreamedResponse
    {
        $rows = $this->templateRows($questions);

        $response = $this->writeCsv($rows);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="modelo-importacao-pacientes.csv"');

        return $response;
    }

    /**
     * @param  array<int, int>  $negrito  números (1-based) das linhas em negrito
     */
    private function writeXlsx(array $rows, string $filename, array $negrito = []): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        // Comparação estrita: sem ela o PhpSpreadsheet grava 0 como célula vazia.
        $sheet->fromArray($rows, null, 'A1', true);

        foreach ($negrito as $linha) {
            $sheet->getStyle("{$linha}:{$linha}")->getFont()->setBold(true);
        }

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}
