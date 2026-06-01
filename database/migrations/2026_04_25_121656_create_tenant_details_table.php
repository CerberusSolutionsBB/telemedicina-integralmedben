<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants_details', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('descricao')->nullable();
            $table->string('slug')->nullable();
            $table->string('path_arquivos')->nullable();

            $table->string('tenant_id')->nullable();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('cor_primaria')->nullable();
            $table->string('cor_secundaria')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants_details');
    }
};
