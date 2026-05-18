<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();

            $table->string('nome');
            $table->string('sobrenome')->nullable();

            $table->string('cpf', 11)
                ->unique()
                ->index();

            $table->date('data_nascimento')->nullable();

            $table->string('email')->nullable();

            $table->json('telefones')->nullable();

            $table->enum('status', [
                'pendente',
                'ativo',
                'inativo',
                'bloqueado',
            ])
                ->default('pendente')
                ->index();

            $table->json('metadados')->nullable();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};