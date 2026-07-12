<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class LatexCheckPermissions extends Command
{
    protected $signature = 'latex:check';

    protected $description = 'Verificar se o ambiente LaTeX está configurado corretamente no servidor';

    public function handle(): int
    {
        $this->newLine();
        $this->info('Verificando ambiente LaTeX...');
        $this->newLine();

        $allOk = true;

        $allOk = $this->checkPdflatex() && $allOk;
        $allOk = $this->checkConfig() && $allOk;
        $allOk = $this->checkTempDir() && $allOk;
        $allOk = $this->checkStorageLink() && $allOk;
        $allOk = $this->checkCompilation() && $allOk;

        $this->newLine();

        if ($allOk) {
            $this->info('Tudo certo! O ambiente LaTeX está configurado corretamente.');
        } else {
            $this->error('Alguns itens falharam. Corrija os problemas acima antes de usar o gerador de cartão.');
        }

        return $allOk ? self::SUCCESS : self::FAILURE;
    }

    private function checkPdflatex(): bool
    {
        $bin = config('latex.bin', 'pdflatex');
        $path = trim(shell_exec("which {$bin} 2>/dev/null") ?? '');

        if (empty($path)) {
            $this->error("[ERRO] pdflatex NÃO encontrado! Instale com: apt install texlive-full");
            return false;
        }

        $version = trim(shell_exec("{$path} --version 2>/dev/null | head -1") ?? '');
        $this->info("[OK] pdflatex encontrado: {$path}");
        $this->comment("    Versão: {$version}");

        return true;
    }

    private function checkConfig(): bool
    {
        $bin = config('latex.bin');
        $tempDir = config('latex.temp_dir');
        $options = config('latex.options');

        $this->info("[OK] Config: bin={$bin}, options={$options}");
        $this->comment("    temp_dir={$tempDir}");

        return true;
    }

    private function checkTempDir(): bool
    {
        $tempDir = config('latex.temp_dir');

        if (!File::isDirectory($tempDir)) {
            $this->error("[ERRO] Diretório temp NÃO existe: {$tempDir}");
            $this->comment("    Criando diretório...");

            if (File::makeDirectory($tempDir, 0755, true, true)) {
                $this->info("[OK] Diretório criado com sucesso: {$tempDir}");
            } else {
                $this->error("[ERRO] Não foi possível criar o diretório: {$tempDir}");
                return false;
            }
        } else {
            $this->info("[OK] Diretório temp existe: {$tempDir}");
        }

        $testFile = $tempDir . '/.permission_test';
        File::put($testFile, 'test');

        if (File::exists($testFile)) {
            File::delete($testFile);
            $this->info("[OK] Permissão de escrita: www-data pode escrever");
            return true;
        }

        $this->error("[ERRO] Sem permissão de escrita em: {$tempDir}");
        $this->comment("    Execute: chown -R www-data:www-data {$tempDir}");
        return false;
    }

    private function checkStorageLink(): bool
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (is_link($link)) {
            $realTarget = readlink($link);
            $this->info("[OK] Storage link: public/storage -> {$realTarget}");
            return true;
        }

        $this->error("[ERRO] Storage link NÃO existe!");
        $this->comment("    Execute: php artisan storage:link");
        return false;
    }

    private function checkCompilation(): bool
    {
        $tempDir = config('latex.temp_dir');
        File::makeDirectory($tempDir, 0755, true, true);

        $testTex = $tempDir . '/latex-check-test.tex';
        $testPdf = $tempDir . '/latex-check-test.pdf';

        $tex = <<<'LATEX'
\documentclass[border=0pt]{standalone}
\begin{document}
Hello World
\end{document}
LATEX;

        File::put($testTex, $tex);

        $command = sprintf(
            '%s %s -output-directory=%s %s 2>&1',
            config('latex.bin'),
            config('latex.options'),
            escapeshellarg($tempDir),
            escapeshellarg($testTex)
        );

        exec($command, $output, $returnCode);

        File::delete([
            $testTex,
            $testPdf,
            $tempDir . '/latex-check-test.aux',
            $tempDir . '/latex-check-test.log',
        ]);

        if ($returnCode === 0) {
            $this->info('[OK] Teste de compilação: PDF gerado com sucesso');
            return true;
        }

        $this->error('[ERRO] Teste de compilação FALHOU! pdflatex retornou código ' . $returnCode);
        $this->comment('    Últimas linhas de saída:');
        foreach (array_slice($output, -10) as $line) {
            $this->comment("    {$line}");
        }

        return false;
    }
}
