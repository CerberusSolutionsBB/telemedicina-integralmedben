<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paciente_vinculo_familiares', function (Blueprint $table) {
            // codDependente devolvido pela SIPROV: reenviado para atualizar em vez de duplicar.
            $table->unsignedInteger('siprov_cod_dependente')->nullable()->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('paciente_vinculo_familiares', function (Blueprint $table) {
            $table->dropColumn('siprov_cod_dependente');
        });
    }
};
