<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Serviços vinculados a cada processo (banco do tenant).
        Schema::create('processos_servicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->cascadeOnDelete();
            // Serviço em uso num processo não é excluído (inative-o).
            $table->foreignId('servico_id')->constrained('servicos')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['processo_id', 'servico_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processos_servicos');
    }
};
