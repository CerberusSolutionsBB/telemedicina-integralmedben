<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convênios do parceiro (banco do tenant). Alterações vão para a auditoria central.
        Schema::create('convenios', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 120);
            $table->text('descricao')->nullable();
            $table->string('icone', 50); // nome do ícone (componente lucide, ex.: HeartPulse)
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convenios');
    }
};
