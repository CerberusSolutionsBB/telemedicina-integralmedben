<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cupons de desconto na venda de planos (vínculo do beneficiário ao plano).
 * tenant_id null = cupom global, criado pelo admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cupons', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            // venda (agora) | fatura (futuro, junto com o faturamento)
            $table->string('alvo', 20)->default('venda');
            $table->string('tipo', 20); // percentual | valor_fixo
            $table->decimal('valor', 10, 2);
            $table->string('tenant_id')->nullable();
            $table->unsignedBigInteger('criado_por_id')->nullable();
            $table->string('criado_por_nome')->nullable();
            $table->string('origem', 30)->default('manual');
            $table->dateTime('inicio_em');
            $table->dateTime('expira_em');
            $table->unsignedInteger('limite_uso_total')->nullable();
            $table->unsignedInteger('limite_uso_por_cpf')->default(1);
            $table->unsignedInteger('usos')->default(0);
            $table->string('status', 20)->default('ativo'); // ativo | pausado | cancelado
            $table->decimal('valor_minimo', 10, 2)->default(0);
            $table->string('aplicavel_a', 30)->default('todos'); // todos | planos | primeiro_contrato
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index('status');
            $table->index('expira_em');
        });

        Schema::create('cupom_planos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cupom_id')->constrained('cupons')->cascadeOnDelete();
            $table->string('cod_plano');

            $table->unique(['cupom_id', 'cod_plano']);
        });

        Schema::create('cupom_usos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cupom_id')->constrained('cupons')->cascadeOnDelete();
            $table->string('tenant_id');
            $table->string('cpf', 14);
            $table->string('paciente_nome')->nullable();
            $table->string('cod_plano');
            $table->string('origem_type');
            $table->unsignedBigInteger('origem_id');
            $table->decimal('valor_original', 10, 2);
            $table->decimal('desconto', 10, 2);
            $table->decimal('valor_final', 10, 2);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('usuario_nome')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('estornado_em')->nullable();
            $table->timestamps();

            $table->index(['cupom_id', 'cpf']);
            $table->index(['origem_type', 'origem_id']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupom_usos');
        Schema::dropIfExists('cupom_planos');
        Schema::dropIfExists('cupons');
    }
};
