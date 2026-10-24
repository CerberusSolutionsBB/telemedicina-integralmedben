<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Processos do parceiro (banco do tenant). Alterações vão para a auditoria central.
        Schema::create('processos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 120);
            $table->text('descricao')->nullable();
            $table->string('icone', 50); // nome do ícone (componente lucide, ex.: Workflow)
            $table->boolean('status')->default(true);
            // Liga os campos personalizados do processo (tabela processos_campos).
            $table->boolean('campo_status')->default(false);
            $table->decimal('valor', 10, 2)->default(0);
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processos');
    }
};
