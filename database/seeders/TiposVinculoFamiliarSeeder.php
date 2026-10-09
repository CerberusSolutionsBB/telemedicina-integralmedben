<?php

namespace Database\Seeders;

use App\Models\TipoVinculoFamiliar;
use Illuminate\Database\Seeder;

/**
 * Tipos de membro da família do plano familiar: exatamente os parentescos aceitos
 * pela SIPROV no cadastro de dependente (o nome exibido é o próprio código).
 *
 * Idempotente e só age em contexto de tenant (no central não faz nada).
 *   php artisan tenants:seed --class=TiposVinculoFamiliarSeeder
 */
class TiposVinculoFamiliarSeeder extends Seeder
{
    public const TIPOS = [
        'CONJUGE', 'PAI', 'FILHO', 'IRMAO', 'AVO', 'TIO', 'SOBRINHO', 'PRIMO', 'NETO', 'SOGRO', 'OUTRO',
        'PET_CANINO', 'PET_FELINO', 'PET_OUTROS', 'CUNHADO', 'GENRO', 'ENTEADO', 'PADRASTO',
    ];

    public function run(): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        $ordem = 0;

        foreach (self::TIPOS as $codigo) {
            TipoVinculoFamiliar::updateOrCreate(['codigo' => $codigo], ['nome' => $codigo, 'ordem' => ++$ordem]);
        }

        TipoVinculoFamiliar::whereNotIn('codigo', self::TIPOS)->delete();
    }
}
