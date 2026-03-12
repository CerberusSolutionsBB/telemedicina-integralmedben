<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('api');              // 'clube_beneficios' | 'telemedicina'
            $table->string('tenant_id')->index();
            $table->unsignedBigInteger('patient_id')->index();
            $table->string('status');           // 'success' | 'failed'
            $table->json('payload')->nullable(); // dados enviados
            $table->json('response')->nullable(); // resposta da API
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_api_logs');
    }
};
