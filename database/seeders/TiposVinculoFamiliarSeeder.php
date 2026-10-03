<?php

namespace Database\Seeders;

use App\Models\TipoVinculoFamiliar;
use Illuminate\Database\Seeder;

/**
 * Tipos de membro da família do plano familiar.
 *
 * Idempotente e só age em contexto de tenant (no central não faz nada).
 *   php artisan tenants:seed --class=TiposVinculoFamiliarSeeder
 */
class TiposVinculoFamiliarSeeder extends Seeder
{
    public const TIPOS = [
        'CONJUGE' => 'Cônjuge / Companheiro(a)',
        'FILHO' => 'Filho',
        'FILHA' => 'Filha',
        'MAE' => 'Mãe',
        'PAI' => 'Pai',
        'IRMAO' => 'Irmão',
        'IRMA' => 'Irmã',
        'AVO' => 'Avô / Avó',
        'NETO' => 'Neto(a)',
        'ENTEADO' => 'Enteado(a)',
        'SOGRO' => 'Sogro(a)',
        'OUTRO' => 'Outro',
    ];

    public function run(): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        $ordem = 0;

        foreach (self::TIPOS as $codigo => $nome) {
            TipoVinculoFamiliar::updateOrCreate(['codigo' => $codigo], ['nome' => $nome, 'ordem' => ++$ordem]);
        }
    }
}
