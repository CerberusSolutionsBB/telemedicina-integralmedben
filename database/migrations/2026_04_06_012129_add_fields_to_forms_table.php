<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->string('btn_confirmar_descricao')->nullable()->after('id');
            $table->text('sub_descricao')->nullable()->after('btn_confirmar_descricao');
            $table->text('observacao')->nullable()->after('sub_descricao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn([
                'btn_confirmar_descricao',
                'sub_descricao',
                'observacao',
            ]);
        });
    }
};
