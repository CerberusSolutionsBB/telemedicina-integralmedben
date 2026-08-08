<?php

namespace App\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CheckStoragePermissions extends Command
{
    protected $signature = 'storage:check-permissions {--dry-run : Apenas reportar os problemas, sem corrigir}';

    protected $description = 'Verifica se as permissões dos diretórios/arquivos de storage estão corretas e corrige quando necessário';

    private const DIR_PERMISSION = 0755;

    private const FILE_PERMISSION = 0644;

    private const DISKS = ['public', 'tenants'];

    private int $fixed = 0;

    private int $unresolved = 0;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->fixed = 0;
        $this->unresolved = 0;

        $this->newLine();
        $this->info('Verificando permissões de storage...');
        $this->newLine();

        foreach (self::DISKS as $disk) {
            $this->checkDisk($disk, $dryRun);
        }

        $this->checkSymlinks($dryRun);

        $this->newLine();

        $total = $this->fixed + $this->unresolved;

        if ($total === 0) {
            $this->info('Tudo certo! Todas as permissões estão corretas.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("{$total} problema(s) encontrado(s). Execute sem --dry-run para corrigir.");

            return self::FAILURE;
        }

        if ($this->fixed > 0) {
            $this->info("{$this->fixed} problema(s) corrigido(s).");
        }

        if ($this->unresolved > 0) {
            $this->error("{$this->unresolved} problema(s) NÃO puderam ser corrigidos automaticamente. Veja os erros acima.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function checkDisk(string $disk, bool $dryRun): void
    {
        $root = Storage::disk($disk)->path('');
        $before = $this->fixed + $this->unresolved;

        $this->comment("Disco '{$disk}' ({$root})");

        if (! is_dir($root)) {
            $this->error("  [ERRO] Diretório raiz não existe: {$root}");

            if ($dryRun) {
                $this->unresolved++;

                return;
            }

            if (@mkdir($root, self::DIR_PERMISSION, true)) {
                $this->info('  [CORRIGIDO] Diretório raiz criado.');
                $this->fixed++;
            } else {
                $this->error('  [ERRO] Não foi possível criar o diretório raiz.');
                $this->unresolved++;
            }

            return;
        }

        $this->checkPath($root, true, $dryRun);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $this->checkPath($item->getPathname(), $item->isDir(), $dryRun);
        }

        if ($this->fixed + $this->unresolved === $before) {
            $this->info('  [OK] Nenhum problema encontrado.');
        }
    }

    private function checkPath(string $path, bool $isDir, bool $dryRun): void
    {
        if (is_link($path) && ! file_exists($path)) {
            $this->error("  [ERRO] Link quebrado encontrado: {$path} -> ".readlink($path)." (destino não existe)");
            $this->unresolved++;

            return;
        }

        $expected = $isDir ? self::DIR_PERMISSION : self::FILE_PERMISSION;
        $current = fileperms($path) & 0777;

        if ($current === $expected) {
            return;
        }

        $type = $isDir ? 'diretório' : 'arquivo';
        $currentOctal = sprintf('%o', $current);
        $expectedOctal = sprintf('%o', $expected);

        if ($dryRun) {
            $this->warn("  [DIVERGENTE] {$type} {$path} está com {$currentOctal}, esperado {$expectedOctal}");
            $this->unresolved++;

            return;
        }

        if (@chmod($path, $expected)) {
            $this->info("  [CORRIGIDO] {$type} {$path}: {$currentOctal} -> {$expectedOctal}");
            $this->fixed++;
        } else {
            $this->error("  [ERRO] Não foi possível alterar permissão de {$type} {$path}");
            $this->unresolved++;
        }
    }

    private function checkSymlinks(bool $dryRun): void
    {
        $before = $this->fixed + $this->unresolved;
        $this->comment('Links simbólicos');

        foreach (config('filesystems.links', []) as $link => $target) {
            if (! file_exists($target)) {
                continue;
            }

            if (is_link($link) && realpath(readlink($link)) === realpath($target)) {
                continue;
            }

            // Link já existe, mas aponta para o lugar errado (ou está quebrado).
            // Um symlink nunca guarda dados de verdade, então é seguro recriá-lo.
            if (is_link($link)) {
                $currentTarget = readlink($link);

                if ($dryRun) {
                    $this->warn("  [DIVERGENTE] {$link} aponta para {$currentTarget}, esperado {$target}");
                    $this->unresolved++;

                    continue;
                }

                if (unlink($link) && symlink($target, $link)) {
                    $this->info("  [CORRIGIDO] Link recriado: {$link} -> {$target}");
                    $this->fixed++;
                } else {
                    $this->error("  [ERRO] Não foi possível recriar o link {$link}");
                    $this->unresolved++;
                }

                continue;
            }

            // Não existe nada nesse caminho ainda.
            if (! file_exists($link)) {
                if ($dryRun) {
                    $this->warn("  [FALTANDO] {$link} -> {$target}");
                    $this->unresolved++;

                    continue;
                }

                if (symlink($target, $link)) {
                    $this->info("  [CORRIGIDO] Link criado: {$link} -> {$target}");
                    $this->fixed++;
                } else {
                    $this->error("  [ERRO] Não foi possível criar o link {$link}");
                    $this->unresolved++;
                }

                continue;
            }

            // Existe um arquivo/diretório real (não um link) nesse caminho — não é seguro
            // remover automaticamente, pois pode conter dados reais.
            $this->error("  [ERRO] {$link} já existe como arquivo/diretório real (não é um link) e não aponta para {$target}.");
            $this->comment('    Verifique manualmente antes de remover — pode conter dados reais.');
            $this->unresolved++;
        }

        if ($this->fixed + $this->unresolved === $before) {
            $this->info('  [OK] Todos os links simbólicos estão corretos.');
        }
    }
}
