<?php

use Database\Seeders\TiposVinculoFamiliarSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vínculo do familiar passa a usar o parentesco da SIPROV, e o sexo (que antes
     * era deduzido de MAE/FILHA/IRMA...) vira um campo do familiar.
     */
    public function up(): void
    {
        Schema::table('paciente_vinculo_familiares', function (Blueprint $table) {
            $table->string('sexo', 10)->nullable()->after('data_nascimento');
        });

        $conversao = [
            'MAE' => ['PAI', 'Feminino'],
            'FILHA' => ['FILHO', 'Feminino'],
            'IRMA' => ['IRMAO', 'Feminino'],
            'PAI' => ['PAI', 'Masculino'],
            'FILHO' => ['FILHO', 'Masculino'],
            'IRMAO' => ['IRMAO', 'Masculino'],
        ];

        foreach ($conversao as $antigo => [$tipo, $sexo]) {
            DB::table('paciente_vinculo_familiares')->where('tipo', $antigo)->update(['tipo' => $tipo, 'sexo' => $sexo]);
        }

        (new TiposVinculoFamiliarSeeder)->run();
    }

    public function down(): void
    {
        Schema::table('paciente_vinculo_familiares', function (Blueprint $table) {
            $table->dropColumn('sexo');
        });
    }
};
