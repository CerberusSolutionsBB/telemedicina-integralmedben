<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_planos', function (Blueprint $table) {
            // Valor em reais cobrado pelo plano neste tenant.
            $table->decimal('valor', 10, 2)->nullable()->after('saldo');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_planos', function (Blueprint $table) {
            $table->dropColumn('valor');
        });
    }
};
