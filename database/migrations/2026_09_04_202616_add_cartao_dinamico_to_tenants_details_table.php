<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants_details', function (Blueprint $table) {
            $table->string('cartao_cor_primaria')->nullable()->after('configuracao');
            $table->string('cartao_cor_secundaria')->nullable()->after('cartao_cor_primaria');
            $table->string('cartao_logo')->nullable()->after('cartao_cor_secundaria');
            $table->string('cartao_imagem_frente')->nullable()->after('cartao_logo');
            $table->string('cartao_imagem_verso')->nullable()->after('cartao_imagem_frente');
        });
    }

    public function down(): void
    {
        Schema::table('tenants_details', function (Blueprint $table) {
            $table->dropColumn([
                'cartao_cor_primaria',
                'cartao_cor_secundaria',
                'cartao_logo',
                'cartao_imagem_frente',
                'cartao_imagem_verso',
            ]);
        });
    }
};
