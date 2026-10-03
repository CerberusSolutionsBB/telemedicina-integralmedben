<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tipos de membro da família (MAE, PAI...): populados pelo TiposVinculoFamiliarSeeder.
        Schema::create('tipos_vinculo_familiar', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nome');
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });

        // Membros da família do beneficiário no plano familiar.
        Schema::create('paciente_vinculo_familiares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('patients')->cascadeOnDelete();
            $table->string('plano_id'); // código do plano (config siprov.planos.clinica_familiar)
            $table->string('nome');
            $table->string('cpf', 14)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('tipo', 30); // tipos_vinculo_familiar.codigo
            $table->timestamps();

            $table->index(['paciente_id', 'plano_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente_vinculo_familiares');
        Schema::dropIfExists('tipos_vinculo_familiar');
    }
};
