<?php

namespace App\Http\Services\Patient;

use App\Models\Patient;
use App\Models\Tenant;
use App\Support\PatientAnswerFormatter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

class PatientsReportPdfService
{
    public function generate(string $tenantId): string
    {
        $patients = Patient::with('answers.question')->latest()->get();
        $questions = $patients->flatMap(fn ($p) => $p->answers->pluck('question'))->unique('id')->values();
        $tenant = Tenant::with('details')->find($tenantId);

        $tex = $this->buildLatex($patients, $questions, $tenant);

        return $this->compile($tex);
    }

    private function buildLatex($patients, $questions, ?Tenant $tenant): string
    {
        $tenantName = $tenant?->details->first()?->descricao ?: ($tenant?->name ?: 'Relatório de Pacientes');
        $generatedAt = now()->setTimezone('America/Sao_Paulo')->format('d/m/Y \à\s H:i');
        $total = $patients->count();
        $subtitle = $total.' '.($total === 1 ? 'paciente' : 'pacientes').' --- Gerado em '.$generatedAt;

        $logoPath = null;
        if ($tenant?->photo_path) {
            $absolute = base_path('storage/app/public/'.$tenant->photo_path);
            if (file_exists($absolute)) {
                $logoPath = $absolute;
            }
        }

        $columnSpec = 'c'.str_repeat('X', $questions->count()).'c';

        $headerCells = ['\textbf{\#}'];
        foreach ($questions as $question) {
            $headerCells[] = '\textbf{'.$this->escapeLatex($question->title).'}';
        }
        $headerCells[] = '\textbf{Data}';
        $headerRow = implode(' & ', $headerCells).' \\\\';

        $bodyRows = [];
        foreach ($patients as $patient) {
            $cells = [$this->escapeLatex((string) $patient->id)];

            $answers = $patient->answers->keyBy('question_id');
            foreach ($questions as $question) {
                $formatted = PatientAnswerFormatter::formatAnswer($answers->get($question->id)?->answer, $question);
                $cells[] = $this->escapeLatex($formatted ?: '-');
            }

            $cells[] = $this->escapeLatex($patient->created_at->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i'));

            $bodyRows[] = implode(' & ', $cells).' \\\\';
        }

        $body = $patients->isEmpty()
            ? '\multicolumn{'.($questions->count() + 2).'}{c}{\textit{Não existem pacientes registrados.}} \\\\'
            : implode("\n", $bodyRows);

        $logoBlock = $logoPath
            ? '\includegraphics[height=1.6cm]{'.$this->escapeLatexPath($logoPath).'}'
            : '';

        // Nowdoc (aspas simples): nenhuma interpolação/escape do PHP é aplicada,
        // evitando que sequências como \v (vspace) ou \f (familydefault) sejam
        // interpretadas como caracteres de escape (vertical tab / form feed).
        $template = <<<'LATEX'
\documentclass{article}
\usepackage[a4paper,landscape,margin=1.2cm]{geometry}
\usepackage[utf8]{inputenc}
\usepackage[T1]{fontenc}
\usepackage[brazilian]{babel}
\usepackage{helvet}
\renewcommand{\familydefault}{\sfdefault}
\usepackage{graphicx}
\usepackage{array}
\usepackage{tabularx}
\usepackage{ltablex}
\keepXColumns
\usepackage{booktabs}
\pagestyle{empty}
\setlength{\parindent}{0pt}

\begin{document}

\begin{center}
__LOGO__

{\Large\textbf{__TENANT_NAME__}}

\small __SUBTITLE__
\end{center}

\vspace{0.8em}

\begin{tabularx}{\linewidth}{__COLUMN_SPEC__}
\toprule
__HEADER_ROW__
\midrule
\endhead
__BODY__
\bottomrule
\end{tabularx}

\end{document}
LATEX;

        return strtr($template, [
            '__LOGO__' => $logoBlock,
            '__TENANT_NAME__' => $this->escapeLatex($tenantName),
            '__SUBTITLE__' => $this->escapeLatex($subtitle),
            '__COLUMN_SPEC__' => $columnSpec,
            '__HEADER_ROW__' => $headerRow,
            '__BODY__' => $body,
        ]);
    }

    private function compile(string $tex): string
    {
        $workDir = storage_path('app/tmp/patients-report-'.Str::random(16));
        File::ensureDirectoryExists($workDir);

        try {
            $texPath = $workDir.'/report.tex';
            File::put($texPath, $tex);

            for ($i = 0; $i < 2; $i++) {
                $result = Process::path($workDir)
                    ->timeout(60)
                    ->run([
                        $this->pdflatexBinary(),
                        '-interaction=nonstopmode',
                        '-halt-on-error',
                        '-output-directory='.$workDir,
                        $texPath,
                    ]);

                if ($result->failed()) {
                    throw new RuntimeException(
                        'Falha ao compilar o relatório em PDF: '.$result->output().$result->errorOutput()
                    );
                }
            }

            $pdfPath = $workDir.'/report.pdf';

            if (! file_exists($pdfPath)) {
                throw new RuntimeException('PDF não foi gerado pelo pdflatex.');
            }

            return file_get_contents($pdfPath);
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    private function escapeLatex(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = str_replace('\\', "\x00BACKSLASH\x00", $value);

        $value = strtr($value, [
            '{' => '\{',
            '}' => '\}',
            '$' => '\$',
            '&' => '\&',
            '#' => '\#',
            '%' => '\%',
            '_' => '\_',
            '~' => '\textasciitilde{}',
            '^' => '\textasciicircum{}',
        ]);

        return str_replace("\x00BACKSLASH\x00", '\textbackslash{}', $value);
    }

    private function escapeLatexPath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function pdflatexBinary(): string
    {
        foreach (['/usr/bin/pdflatex', '/usr/local/bin/pdflatex'] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return 'pdflatex';
    }
}
