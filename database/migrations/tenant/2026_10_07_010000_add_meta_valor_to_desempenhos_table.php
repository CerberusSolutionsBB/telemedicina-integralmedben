<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Meta em R$ das funções financeiras (ex.: comissão por venda de plano).
        Schema::table('desempenhos', function (Blueprint $table) {
            $table->decimal('meta_valor', 12, 2)->nullable()->after('meta');
        });
    }

    public function down(): void
    {
        Schema::table('desempenhos', function (Blueprint $table) {
            $table->dropColumn('meta_valor');
        });
    }
};
