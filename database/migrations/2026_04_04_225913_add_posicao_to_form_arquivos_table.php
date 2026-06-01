<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_arquivos', function (Blueprint $table) {
            $table->string('posicao')->default('centro')->after('form_id');
            $table->string('tipo')->nullable()->after('posicao');

        });
    }

    public function down(): void
    {
        Schema::table('form_arquivos', function (Blueprint $table) {
            $table->dropColumn('posicao');
            $table->dropColumn('tipo');
        });
    }
};
