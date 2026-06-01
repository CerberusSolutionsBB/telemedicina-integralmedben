<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arquivos', function (Blueprint $table) {
            $table->id();
            $table->string('nome_original');
            $table->string('nome_armazenado');
            $table->string('caminho');
            $table->string('extensao', 10);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamanho');
            $table->string('disk')->default('local');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arquivos');
    }
};
