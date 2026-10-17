<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Meta passa a valer para vários planos: cod_plano (um) vira cod_planos (lista).
     * Metas de um plano só viram a lista com esse plano; "todos os planos" continua null.
     */
    public function up(): void
    {
        Schema::table('desempenhos', function (Blueprint $table) {
            $table->json('cod_planos')->nullable()->after('escopo_plano');
        });

        DB::table('desempenhos')->whereNotNull('cod_plano')->get(['id', 'cod_plano'])->each(
            fn ($meta) => DB::table('desempenhos')->where('id', $meta->id)->update(['cod_planos' => json_encode([(string) $meta->cod_plano])])
        );

        Schema::table('desempenhos', function (Blueprint $table) {
            $table->dropColumn('cod_plano');
        });
    }

    public function down(): void
    {
        Schema::table('desempenhos', function (Blueprint $table) {
            $table->string('cod_plano')->nullable()->after('escopo_plano');
        });

        // Volta só o primeiro plano de cada meta (a coluna antiga guardava um).
        DB::table('desempenhos')->whereNotNull('cod_planos')->get(['id', 'cod_planos'])->each(
            fn ($meta) => DB::table('desempenhos')->where('id', $meta->id)->update(['cod_plano' => json_decode($meta->cod_planos, true)[0] ?? null])
        );

        Schema::table('desempenhos', function (Blueprint $table) {
            $table->dropColumn('cod_planos');
        });
    }
};
