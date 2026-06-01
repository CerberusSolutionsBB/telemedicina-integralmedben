<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users');
            $table->string('tenant_id')->nullable();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');
            $table->foreignId('form_id')
                ->nullable()
                ->constrained('forms');
            $table->enum('origem', ['CENTRAL', 'CLIENTE'])
                ->default('CENTRAL');
            $table->boolean('ativo')->default(true);
            $table->boolean('principal')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'form_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants_forms');
    }
};
