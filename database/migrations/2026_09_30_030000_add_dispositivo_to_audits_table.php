<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('audit.drivers.database.connection'))
            ->table(config('audit.drivers.database.table', 'audits'), function (Blueprint $table) {
                // { tipo: celular|tablet|computador, sistema, navegador } extraído do user agent.
                $table->json('dispositivo')->nullable()->after('user_agent');
            });
    }

    public function down(): void
    {
        Schema::connection(config('audit.drivers.database.connection'))
            ->table(config('audit.drivers.database.table', 'audits'), function (Blueprint $table) {
                $table->dropColumn('dispositivo');
            });
    }
};
