<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->string('plano_id')->nullable()->after('configuracao');
            $table->text('situacao')->nullable()->after('plano_id');
            $table->integer('dia_vencimento')->nullable()->after('situacao');
            $table->boolean('status_beneficio')->default(true)->after('dia_vencimento');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(['plano_id', 'situacao', 'dia_vencimento', 'status_beneficio']);
        });
    }
};
