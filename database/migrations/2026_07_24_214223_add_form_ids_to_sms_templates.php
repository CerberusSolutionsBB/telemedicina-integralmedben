<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->json('form_ids')->nullable()->after('plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->dropColumn('form_ids');
        });
    }
};
