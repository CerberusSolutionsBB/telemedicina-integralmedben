<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants_details', function (Blueprint $table) {
            $table->string('cartao_cor_texto')->nullable()->after('cartao_cor_secundaria');
            $table->string('cartao_fonte')->nullable()->after('cartao_cor_texto');
        });
    }

    public function down(): void
    {
        Schema::table('tenants_details', function (Blueprint $table) {
            $table->dropColumn(['cartao_cor_texto', 'cartao_fonte']);
        });
    }
};
