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

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->newLine();
        $this->info('Verificando permissões de storage...');
        $this->newLine();

        $totalIssues = 0;

        foreach (self::DISKS as $disk) {
            $totalIssues += $this->checkDisk($disk, $dryRun);
        }

        $totalIssues += $this->checkSymlinks($dryRun);

        $this->newLine();

        if ($totalIssues === 0) {
            $this->info('Tudo certo! Todas as permissões estão corretas.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("{$totalIssues} problema(s) encontrado(s). Execute sem --dry-run para corrigir.");

            return self::FAILURE;
        }

        $this->info("{$totalIssues} problema(s) corrigido(s).");

        return self::SUCCESS;
    }

    private function checkDisk(string $disk, bool $dryRun): int
    {
        $root = Storage::disk($disk)->path('');
        $issues = 0;

        $this->comment("Disco '{$disk}' ({$root})");

        if (! is_dir($root)) {
            $this->error("  [ERRO] Diretório raiz não existe: {$root}");

            if (! $dryRun) {
                mkdir($root, self::DIR_PERMISSION, true);
                $this->info('  [CORRIGIDO] Diretório raiz criado.');
            }

            return 1;
        }

        $issues += $this->checkPath($root, true, $dryRun);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $issues += $this->checkPath($item->getPathname(), $item->isDir(), $dryRun);
        }

        if ($issues === 0) {
            $this->info('  [OK] Nenhum problema encontrado.');
        }

        return $issues;
    }

    private function checkPath(string $path, bool $isDir, bool $dryRun): int
    {
        $expected = $isDir ? self::DIR_PERMISSION : self::FILE_PERMISSION;
        $current = fileperms($path) & 0777;

        if ($current === $expected) {
            return 0;
        }

        $type = $isDir ? 'diretório' : 'arquivo';
        $currentOctal = sprintf('%o', $current);
        $expectedOctal = sprintf('%o', $expected);

        if ($dryRun) {
            $this->warn("  [DIVERGENTE] {$type} {$path} está com {$currentOctal}, esperado {$expectedOctal}");

            return 1;
        }

        chmod($path, $expected);
        $this->info("  [CORRIGIDO] {$type} {$path}: {$currentOctal} -> {$expectedOctal}");

        return 1;
    }

    private function checkSymlinks(bool $dryRun): int
    {
        $issues = 0;
        $this->comment('Links simbólicos');

        foreach (config('filesystems.links', []) as $link => $target) {
            if (! file_exists($target)) {
                continue;
            }

            if (is_link($link) && realpath(readlink($link)) === realpath($target)) {
                continue;
            }

            $issues++;

            if (! is_link($link) && ! file_exists($link)) {
                $this->warn("  [FALTANDO] {$link} -> {$target}");

                if (! $dryRun) {
                    symlink($target, $link);
                    $this->info("  [CORRIGIDO] Link criado: {$link} -> {$target}");
                }

                continue;
            }

            $this->error("  [ERRO] {$link} não aponta corretamente para {$target}.");
            $this->comment('    Execute: php artisan storage:link');
        }

        if ($issues === 0) {
            $this->info('  [OK] Todos os links simbólicos estão corretos.');
        }

        return $issues;
    }
}
