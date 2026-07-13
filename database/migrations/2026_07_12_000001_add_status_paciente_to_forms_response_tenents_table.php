<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms_response_tenents', function (Blueprint $table) {
            $table->boolean('status_paciente')->default(false)->after('response_id');
        });
    }

    public function down(): void
    {
        Schema::table('forms_response_tenents', function (Blueprint $table) {
            $table->dropColumn('status_paciente');
        });
    }
};
