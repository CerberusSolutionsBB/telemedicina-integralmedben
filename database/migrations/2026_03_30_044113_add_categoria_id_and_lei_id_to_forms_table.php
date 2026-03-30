<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->foreignId('categoria_id')
                ->nullable()
                ->after('id')
                ->constrained('form_categories');

            $table->foreignId('lei_id')
                ->nullable()
                ->after('categoria_id')
                ->constrained('leis');

            $table->index('categoria_id');
            $table->index('lei_id');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropForeign(['lei_id']);
            $table->dropIndex(['categoria_id']);
            $table->dropIndex(['lei_id']);
            $table->dropColumn(['categoria_id', 'lei_id']);
        });
    }
};