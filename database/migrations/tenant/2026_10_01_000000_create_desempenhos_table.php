<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Metas de desempenho dos usuários do tenant.
        Schema::create('desempenhos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('funcao'); // registro_beneficiario_plano
            $table->string('escopo_plano')->default('todos'); // todos | plano
            $table->string('cod_plano')->nullable();
            $table->string('tipo_meta')->default('individual'); // individual | coletiva
            $table->unsignedInteger('meta');
            $table->date('data_inicio');
            $table->date('prazo');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Perfis (roles) cujos usuários participam da meta.
        Schema::create('desempenho_role', function (Blueprint $table) {
            $table->foreignId('desempenho_id')->constrained('desempenhos')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['desempenho_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desempenho_role');
        Schema::dropIfExists('desempenhos');
    }
};
