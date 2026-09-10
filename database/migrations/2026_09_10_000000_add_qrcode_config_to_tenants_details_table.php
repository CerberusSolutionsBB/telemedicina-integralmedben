<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants_details', function (Blueprint $table) {
            $table->boolean('cartao_qrcode_habilitado')->default(false)->after('cartao_imagem_verso');
            $table->text('cartao_qrcode_dados')->nullable()->after('cartao_qrcode_habilitado');
            $table->string('cartao_verso_texto_info', 255)->nullable()->after('cartao_qrcode_dados');
            $table->text('cartao_verso_rodape')->nullable()->after('cartao_verso_texto_info');
        });
    }

    public function down(): void
    {
        Schema::table('tenants_details', function (Blueprint $table) {
            $table->dropColumn([
                'cartao_qrcode_habilitado',
                'cartao_qrcode_dados',
                'cartao_verso_texto_info',
                'cartao_verso_rodape',
            ]);
        });
    }
};