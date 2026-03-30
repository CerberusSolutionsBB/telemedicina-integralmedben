<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_responses', function (Blueprint $table) {
            $table->boolean('accepted_terms')->default(false)->after('ip_address');
            $table->timestamp('accepted_at')->nullable()->after('accepted_terms');
        });
    }

    public function down(): void
    {
        Schema::table('form_responses', function (Blueprint $table) {
            $table->dropColumn(['accepted_terms', 'accepted_at']);
        });
    }
};