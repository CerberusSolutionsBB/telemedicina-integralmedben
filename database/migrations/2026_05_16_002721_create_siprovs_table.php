<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siprovs', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relacionamento interno
            |--------------------------------------------------------------------------
            */
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users');

            /*
            |--------------------------------------------------------------------------
            | Dados integração
            |--------------------------------------------------------------------------
            */
            $table->string('codigo_integracao')->index();
            $table->string('nome_pessoa');
            $table->string('cpf_cnpj', 20)->index();
            $table->string('email')->nullable();
            $table->string('sexo', 1)->nullable();
            $table->date('data_nascimento')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Plano
            |--------------------------------------------------------------------------
            */
            $table->unsignedBigInteger('cod_loja')->default(5578);
            $table->unsignedBigInteger('cod_plano')->index();

            /*
            |--------------------------------------------------------------------------
            | Benefício
            |--------------------------------------------------------------------------
            */
            $table->boolean('ativo')->default(true);
            $table->unsignedTinyInteger('dia_vencimento')->default(10);
            $table->string('situacao')->default('Ativo');

            /*
            |--------------------------------------------------------------------------
            | Retorno SIPROV
            |--------------------------------------------------------------------------
            */
            $table->json('payload_associado')->nullable();
            $table->json('payload_beneficio')->nullable();

            $table->json('response_associado')->nullable();
            $table->json('response_beneficio')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Controle
            |--------------------------------------------------------------------------
            */
            $table->enum('status', [
                'pending',
                'processing',
                'success',
                'failed',
            ])->default('pending')->index();

            $table->text('error_message')->nullable();

            $table->timestamp('integrated_at')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Constraints
            |--------------------------------------------------------------------------
            */
            $table->unique([
                'codigo_integracao',
                'cpf_cnpj',
                'cod_plano',
            ], 'siprovs_unique_integration');
            $table->softDeletes();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siprovs');
    }
};