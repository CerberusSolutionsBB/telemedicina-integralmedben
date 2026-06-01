<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms_response_tenents', function (Blueprint $table) {
            $table->foreignId('response_id')->nullable()->constrained('form_responses');
        });
    }

    public function down(): void
    {
        Schema::table('forms_response_tenents', function (Blueprint $table) {
            $table->dropColumn('response_id');
        });
    }
};
