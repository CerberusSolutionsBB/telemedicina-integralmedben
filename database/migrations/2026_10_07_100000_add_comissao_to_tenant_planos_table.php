<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configuração de comissão por venda do plano neste tenant.
        Schema::table('tenant_planos', function (Blueprint $table) {
            $table->string('comissao_tipo')->nullable()->after('valor'); // percentual | fixo
            $table->decimal('comissao_valor', 10, 2)->nullable()->after('comissao_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_planos', function (Blueprint $table) {
            $table->dropColumn(['comissao_tipo', 'comissao_valor']);
        });
    }
};
