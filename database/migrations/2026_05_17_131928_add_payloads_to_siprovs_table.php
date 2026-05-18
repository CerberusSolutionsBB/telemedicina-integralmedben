<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siprovs', function (Blueprint $table) {
            $table->json('associado')->nullable()->after('situacao');
            $table->json('beneficio')->nullable()->after('associado');
        });
    }

    public function down(): void
    {
        Schema::table('siprovs', function (Blueprint $table) {
            $table->dropColumn([
                'associado',
                'beneficio',
            ]);
        });
    }
};
