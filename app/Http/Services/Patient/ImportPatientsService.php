<?php

namespace App\Http\Services\Patient;

use App\Enums\PatientSexoEnum;
use App\Enums\StatusRegistroEnum;
use App\Models\Patient;
use App\Models\PatientAnswer;
use App\Support\Formatar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Importa beneficiários da planilha do modelo (ModeloImportacaoPacientesService)
 * ou de arquivos exportados: uma linha por beneficiário, cabeçalho na linha 1.
 * Linhas com erro são puladas e relatadas; as demais são gravadas.
 */
class ImportPatientsService
{
    /** Cabeçalho normalizado (sem acento, minúsculo, sem "*") => campo do paciente. */
    private const CAMPOS = [
        'nome' => 'nome',
        'cpf' => 'cpf',
        'rg' => 'rg',
        'data de nascimento' => 'data_nascimento',
        'data_nascimento' => 'data_nascimento',
        'nascimento' => 'data_nascimento',
        'sexo' => 'sexo',
        'email' => 'email',
        'e-mail' => 'email',
        'telefone' => 'numero',
        'celular' => 'numero',
        'numero' => 'numero',
        'status' => 'status',
    ];

    /** Colunas da exportação que a importação ignora. */
    private const IGNORADAS = ['id', 'data de cadastro', 'plano', 'origem do registro', 'criado por'];

    public function execute(UploadedFile $file, iterable $questions): array
    {
        $rows = in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'], true)
            ? $this->parseCsv($file)
            : $this->parseSpreadsheet($file);

        if (count($rows) < 2) {
            return ['imported' => 0, 'errors' => ['Arquivo vazio: preencha os beneficiários a partir da linha 2.']];
        }

        $header = array_map(fn ($titulo) => $this->normalizar((string) $titulo), array_shift($rows));

        if (! in_array('nome', $header, true)) {
            return ['imported' => 0, 'errors' => ['Coluna "Nome" não encontrada. Use o modelo em Excel disponível na tela de importação.']];
        }

        $perguntas = [];
        foreach ($questions as $question) {
            $perguntas[$this->normalizar($question->title)] = $question->id;
        }

        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $linha = $index + 2;
            $primeira = trim((string) ($row[0] ?? ''));

            // Resumo ao final do arquivo exportado (ExportPatientsService::comResumo): fim dos dados.
            if (str_starts_with($primeira, ExportPatientsService::PREFIXO_RESUMO)) {
                break;
            }

            // Linha em branco (o modelo já vem com linhas formatadas vazias).
            if (collect($row)->every(fn ($valor) => trim((string) $valor) === '')) {
                continue;
            }

            [$dados, $respostas] = $this->separar($header, $row, $perguntas);

            try {
                $paciente = $this->validar($dados);
            } catch (\InvalidArgumentException $e) {
                $errors[] = "Linha {$linha}: {$e->getMessage()}";

                continue;
            }

            try {
                DB::transaction(function () use ($paciente, $respostas) {
                    $patient = Patient::create($paciente + ['status_registro' => StatusRegistroEnum::Importacao]);

                    foreach ($respostas as $questionId => $answer) {
                        PatientAnswer::create([
                            'patient_id' => $patient->id,
                            'question_id' => $questionId,
                            'answer' => $answer,
                        ]);
                    }
                });
                $imported++;
            } catch (Throwable $e) {
                report($e);
                $errors[] = "Linha {$linha}: não foi possível gravar o beneficiário.";
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Separa a linha em campos do paciente e respostas das perguntas.
     *
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    private function separar(array $header, array $row, array $perguntas): array
    {
        $dados = [];
        $respostas = [];

        foreach ($header as $i => $coluna) {
            $valor = $row[$i] ?? null;
            $texto = trim((string) $valor);

            if ($coluna === '' || $texto === '' || in_array($coluna, self::IGNORADAS, true)) {
                continue;
            }

            if (isset(self::CAMPOS[$coluna])) {
                // Data vem como número serial quando a célula está formatada como data no Excel.
                $dados[self::CAMPOS[$coluna]] = is_numeric($valor) && self::CAMPOS[$coluna] === 'data_nascimento' ? $valor : $texto;
            } elseif (isset($perguntas[$coluna])) {
                $respostas[$perguntas[$coluna]] = $texto;
            }
        }

        return [$dados, $respostas];
    }

    /**
     * Normaliza e valida os campos; lança InvalidArgumentException com o motivo.
     *
     * @return array<string, mixed>
     */
    private function validar(array $dados): array
    {
        $nome = $dados['nome'] ?? '';
        if ($nome === '') {
            throw new \InvalidArgumentException('o Nome é obrigatório.');
        }

        $nascimento = $this->data($dados['data_nascimento'] ?? null);
        if (! $nascimento) {
            throw new \InvalidArgumentException('Data de Nascimento obrigatória no formato dd/mm/aaaa.');
        }
        if ($nascimento->isFuture()) {
            throw new \InvalidArgumentException('a Data de Nascimento não pode ser futura.');
        }

        $cpf = null;
        if (isset($dados['cpf'])) {
            // Excel numérico come zeros à esquerda: completa até 11 dígitos.
            $digitos = Formatar::digitos($dados['cpf']);
            $digitos = $digitos === '' ? '' : str_pad($digitos, 11, '0', STR_PAD_LEFT);
            if (strlen($digitos) !== 11) {
                throw new \InvalidArgumentException("CPF \"{$dados['cpf']}\" inválido.");
            }
            if ($this->cpfJaCadastrado($digitos)) {
                throw new \InvalidArgumentException("CPF {$this->mascarar($digitos)} já cadastrado.");
            }
            $cpf = $this->mascarar($digitos);
        }

        $email = $dados['email'] ?? null;
        if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("e-mail \"{$email}\" inválido.");
        }

        $sexo = null;
        if (isset($dados['sexo'])) {
            $sexo = match ($this->normalizar($dados['sexo'])) {
                'masculino', 'm' => PatientSexoEnum::Masculino,
                'feminino', 'f' => PatientSexoEnum::Feminino,
                default => throw new \InvalidArgumentException("Sexo \"{$dados['sexo']}\" inválido (use Masculino ou Feminino)."),
            };
        }

        $status = ! in_array($this->normalizar($dados['status'] ?? ''), ['inativo', '0', 'nao', 'false'], true);

        return [
            'nome' => Str::limit($nome, 255, ''),
            'cpf' => $cpf,
            'rg' => isset($dados['rg']) ? Str::limit($dados['rg'], 20, '') : null,
            'data_nascimento' => $nascimento->toDateString(),
            'sexo' => $sexo,
            'email' => $email,
            'numero' => isset($dados['numero']) ? Formatar::telefone($dados['numero']) : null,
            'status' => $status,
        ];
    }

    /** dd/mm/aaaa, aaaa-mm-dd ou número serial de data do Excel. */
    private function data(mixed $valor): ?Carbon
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            if (is_numeric($valor)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $valor))->startOfDay();
            }

            foreach (['d/m/Y', 'd/m/y', 'Y-m-d', 'd-m-Y', 'd.m.Y'] as $formato) {
                $data = Carbon::createFromFormat('!'.$formato, trim($valor));
                if ($data && $data->format($formato) === trim($valor)) {
                    return $data;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /** Compara só os dígitos: há CPFs gravados com e sem máscara. */
    private function cpfJaCadastrado(string $digitos): bool
    {
        return Patient::where('cpf', $digitos)
            ->orWhere('cpf', $this->mascarar($digitos))
            ->exists();
    }

    private function mascarar(string $digitos): string
    {
        return Formatar::cpf($digitos) ?? $digitos;
    }

    private function normalizar(string $texto): string
    {
        return Str::of($texto)->replace('*', '')->ascii()->lower()->squish()->toString();
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

        // Primeira aba (dados); a aba de instruções do modelo é ignorada.
        // Valores crus: datas chegam como número serial e são convertidas em data().
        return $spreadsheet->getSheet(0)->toArray(null, true, false, false);
    }
}
