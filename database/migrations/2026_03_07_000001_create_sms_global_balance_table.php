<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_global_balance', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('balance')->default(0);
            $table->timestamps();
        });

        // Garante que sempre existe exatamente uma linha
        DB::table('sms_global_balance')->insert(['balance' => 0]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_global_balance');
    }
};
