<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_patients', function (Blueprint $table) {
            $table->dropColumn('cpf');
        });
    }

    public function down(): void
    {
        Schema::table('central_patients', function (Blueprint $table) {
            $table->string('cpf')->after('tenant_id');
        });
    }
};
