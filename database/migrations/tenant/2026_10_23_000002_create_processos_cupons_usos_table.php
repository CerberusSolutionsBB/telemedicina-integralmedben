<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Histórico de usos de cupom de benefício num processo: quem usou, qual cupom e quanto.
        Schema::create('processos_cupons_usos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->cascadeOnDelete();
            // Cupom e usuário podem ser excluídos depois: o código e o nome ficam guardados abaixo.
            $table->foreignId('cupom_beneficio_id')->nullable()->constrained('cupom_beneficios')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_nome')->nullable();
            $table->string('codigo', 20);
            $table->string('desconto_texto', 30); // "15%" ou "R$ 30,00"
            $table->decimal('valor_original', 10, 2);
            $table->decimal('desconto', 10, 2);
            $table->decimal('valor_final', 10, 2);
            $table->timestamps();

            $table->index(['processo_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processos_cupons_usos');
    }
};
