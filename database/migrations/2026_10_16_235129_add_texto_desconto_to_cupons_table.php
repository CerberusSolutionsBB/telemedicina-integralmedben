<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Texto abaixo do desconto no PDF do cupom (ex.: "na consulta com nutricionista").
        // Vazio = texto padrão (Cupom::TEXTO_DESCONTO_PADRAO).
        Schema::table('cupons', function (Blueprint $table) {
            $table->string('texto_desconto', 120)->nullable()->after('valor');
        });
    }

    public function down(): void
    {
        Schema::table('cupons', function (Blueprint $table) {
            $table->dropColumn('texto_desconto');
        });
    }
};
