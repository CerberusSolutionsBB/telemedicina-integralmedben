<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Beneficiários vinculados a planos internos (sem SIPROV/telemedicina).
        Schema::create('tenant_plano_beneficiarios', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('cod_plano');
            $table->unsignedBigInteger('patient_id')->nullable(); // id no banco do tenant
            $table->string('nome');
            $table->string('cpf', 11)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'cpf']);
            $table->index(['tenant_id', 'cod_plano']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_plano_beneficiarios');
    }
};
