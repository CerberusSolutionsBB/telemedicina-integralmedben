<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->foreignId('credencia_cluble_id')
                ->nullable()
                ->after('categoria_id')
                ->constrained('credencias_clubles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropForeign(['credencia_cluble_id']);
            $table->dropColumn('credencia_cluble_id');
        });
    }
};
