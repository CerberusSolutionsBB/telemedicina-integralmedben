<?php

namespace App\Http\Services\Patient;

use App\Models\Patient;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportPatientsService
{
    public function execute(string $format = 'csv'): StreamedResponse
    {
        $patients = Patient::with('answers.question')->latest()->get();
        $questions = $patients->flatMap(fn ($p) => $p->answers->pluck('question'))->unique('id')->values();

        if ($format === 'xlsx') {
            return $this->exportXlsx($patients, $questions);
        }

        return $this->exportCsv($patients, $questions);
    }

    public function generateTemplate($questions, string $format = 'csv'): StreamedResponse
    {
        if ($format === 'xlsx') {
            return $this->templateXlsx($questions);
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
        $headers = $this->headers();
        $questionTitles = $questions->pluck('title')->toArray();
        $headers = array_merge($headers, $questionTitles);

        $example = [
            'João Silva',
            '123.456.789-00',
            '12.345.678-9',
            '1990-01-01',
            'masculino',
            'joao@exemplo.com',
            '(11) 99999-0000',
            '1',
            '',
            '',
        ];

        foreach ($questions as $question) {
            $example[] = '';
        }

        return [$headers, $example];
    }

    private function buildRows($patients, $questions): array
    {
        $headers = $this->headers();
        $questionTitles = $questions->pluck('title')->toArray();
        $headers = array_merge($headers, $questionTitles);

        $rows = [$headers];

        foreach ($patients as $patient) {
            $row = [
                $patient->nome ?? '',
                $patient->cpf ?? '',
                $patient->rg ?? '',
                $patient->data_nascimento?->format('Y-m-d') ?? '',
                $patient->sexo?->value ?? '',
                $patient->email ?? '',
                $patient->numero ?? '',
                $patient->status ? '1' : '0',
                $patient->id,
                $patient->created_at->format('d/m/Y H:i'),
            ];

            $answers = $patient->answers->keyBy('question_id');
            foreach ($questions as $question) {
                $row[] = $answers->get($question->id)?->answer ?? '';
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function writeCsv(array $rows): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        });

        return $response;
    }

    private function exportCsv($patients, $questions): StreamedResponse
    {
        $rows = $this->buildRows($patients, $questions);

        $response = $this->writeCsv($rows);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="pacientes.csv"');

        return $response;
    }

    private function exportXlsx($patients, $questions): StreamedResponse
    {
        $rows = $this->buildRows($patients, $questions);

        return $this->writeXlsx($rows, 'pacientes.xlsx');
    }

    private function templateCsv($questions): StreamedResponse
    {
        $rows = $this->templateRows($questions);

        $response = $this->writeCsv($rows);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="modelo-importacao-pacientes.csv"');

        return $response;
    }

    private function templateXlsx($questions): StreamedResponse
    {
        $rows = $this->templateRows($questions);

        return $this->writeXlsx($rows, 'modelo-importacao-pacientes.xlsx');
    }

    private function writeXlsx(array $rows, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($rows);

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}
