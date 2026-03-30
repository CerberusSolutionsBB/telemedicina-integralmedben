<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users');
            $table->string('title', 500)->index();
            $table->longText('text');
            $table->string('type', 100)->default('lgpd')->index();
            $table->enum('status', ['ativo', 'inativo', 'rascunho'])->default('rascunho');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leis');
    }
};