<?php

namespace App\Http\Services\Patient;

use App\Enums\StatusRegistroEnum;
use App\Models\Patient;
use App\Models\PatientAnswer;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportPatientsService
{
    private array $patientFields = [
        'nome', 'cpf', 'rg', 'data_nascimento', 'sexo',
        'email', 'numero',
    ];

    public function execute(UploadedFile $file, array $questions): array
    {
        $extension = $file->getClientOriginalExtension();
        $rows = [];

        if (in_array($extension, ['csv', 'txt'])) {
            $rows = $this->parseCsv($file);
        } else {
            $rows = $this->parseSpreadsheet($file);
        }

        if (empty($rows)) {
            return ['imported' => 0, 'errors' => ['Arquivo vazio ou formato inválido.']];
        }

        $header = array_shift($rows);
        $header = array_map('trim', $header);

        $questionMap = [];
        foreach ($questions as $question) {
            $questionMap[$question->title] = $question->id;
        }

        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $data = array_combine($header, $row);
            if ($data === false) {
                $errors[] = 'Linha '.($index + 2).': número de colunas não corresponde ao cabeçalho.';

                continue;
            }

            $patientData = [];
            foreach ($this->patientFields as $field) {
                if (isset($data[$field]) && trim((string) $data[$field]) !== '') {
                    $patientData[$field] = trim((string) $data[$field]);
                }
            }

            if (! isset($patientData['numero']) && isset($data['Telefone']) && trim((string) $data['Telefone']) !== '') {
                $patientData['numero'] = trim((string) $data['Telefone']);
            }

            if (! isset($patientData['data_nascimento']) && isset($data['Data de Nascimento']) && trim((string) $data['Data de Nascimento']) !== '') {
                $patientData['data_nascimento'] = trim((string) $data['Data de Nascimento']);
            }

            $answers = [];
            foreach ($data as $columnTitle => $value) {
                $value = trim((string) $value);
                if ($value === '') {
                    continue;
                }

                if (in_array($columnTitle, $this->patientFields, true)
                    || $columnTitle === 'Telefone'
                    || $columnTitle === 'Data de Nascimento'
                    || $columnTitle === 'ID'
                    || $columnTitle === 'Data de Cadastro'
                    || $columnTitle === 'Status'
                ) {
                    continue;
                }

                $questionId = $questionMap[$columnTitle] ?? null;
                if ($questionId === null) {
                    continue;
                }

                $answers[$questionId] = $value;
            }

            try {
                $patientData['status_registro'] = StatusRegistroEnum::Importacao;
                $patient = Patient::create($patientData);
                foreach ($answers as $questionId => $answer) {
                    PatientAnswer::create([
                        'patient_id' => $patient->id,
                        'question_id' => $questionId,
                        'answer' => $answer,
                    ]);
                }
                $imported++;
            } catch (\Exception $e) {
                $errors[] = 'Linha '.($index + 2).': '.$e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function parseSpreadsheet(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        return $rows;
    }
}
