<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_global_balance_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('amount');           // positivo = recarga, negativo = distribuição
            $table->string('type');              // 'recharge' | 'distribute'
            $table->string('tenant_id')->nullable(); // preenchido quando type = distribute
            $table->unsignedBigInteger('added_by')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('balance_after');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_global_balance_logs');
    }
};
