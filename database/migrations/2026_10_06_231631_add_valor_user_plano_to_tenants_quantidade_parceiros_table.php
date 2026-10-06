<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants_quantidade_parceiros', function (Blueprint $table) {
            // Plano do tenant movimentado.
            $table->foreignId('plano_id')->nullable()->after('cod_plano')->constrained('tenant_planos')->nullOnDelete();
            // Quem fez o movimento; null em comandos/formulário público. Sem FK: pode ser usuário do tenant.
            $table->unsignedBigInteger('user_id')->nullable()->index()->after('plano_id');
            // Valor do plano (em reais) no momento do consumo/devolução da vaga.
            $table->decimal('valor', 10, 2)->nullable()->after('quantidade');
        });
    }

    public function down(): void
    {
        Schema::table('tenants_quantidade_parceiros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plano_id');
            $table->dropIndex(['user_id']);
            $table->dropColumn(['user_id', 'valor']);
        });
    }
};
