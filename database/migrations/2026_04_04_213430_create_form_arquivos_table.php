<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_arquivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('arquivo_id');
            $table->unsignedBigInteger('form_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_arquivos');
    }
};
