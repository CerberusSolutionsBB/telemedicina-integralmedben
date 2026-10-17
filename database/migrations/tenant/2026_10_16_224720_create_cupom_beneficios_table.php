<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cupons de benefício do parceiro (banco do tenant): desconto que o beneficiário
        // usa num convênio ou serviço. Diferente dos cupons de venda (banco central).
        Schema::create('cupom_beneficios', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 120);
            $table->text('descricao')->nullable();
            $table->string('icone', 50);
            $table->boolean('status')->default(true);
            // Plano do beneficiário (código, ex.: 331385); null = qualquer plano.
            $table->string('cod_plano')->nullable();
            // Convênio ou serviço onde o desconto vale (ao menos um; validado no request).
            $table->foreignId('convenio_id')->nullable()->constrained('convenios')->restrictOnDelete();
            $table->foreignId('servico_id')->nullable()->constrained('servicos')->restrictOnDelete();
            $table->string('tipo_desconto', 20)->default('percentual'); // percentual | valor_fixo
            $table->decimal('valor_desconto', 10, 2)->default(0);
            // Válido até esta data (inclusive); null = sem prazo.
            $table->date('prazo')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('cod_plano');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupom_beneficios');
    }
};
