<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->string('recipient')->nullable()->after('patient_id');
            $table->string('status')->default('pending')->after('message');
            $table->text('error_message')->nullable()->after('status');
            $table->timestamp('sent_at')->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropColumn(['recipient', 'status', 'error_message', 'sent_at']);
        });
    }
};
