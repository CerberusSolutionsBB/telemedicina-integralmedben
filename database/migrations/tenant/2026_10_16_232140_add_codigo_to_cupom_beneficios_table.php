<?php

use App\Models\CupomBeneficio;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Código que o beneficiário apresenta no convênio/serviço (único no parceiro).
        Schema::table('cupom_beneficios', function (Blueprint $table) {
            $table->string('codigo', 20)->nullable()->after('id');
        });

        // Cupons já cadastrados recebem um código antes de o campo virar obrigatório.
        DB::table('cupom_beneficios')->whereNull('codigo')->pluck('id')->each(
            fn (int $id) => DB::table('cupom_beneficios')->where('id', $id)->update(['codigo' => CupomBeneficio::gerarCodigo()])
        );

        Schema::table('cupom_beneficios', function (Blueprint $table) {
            $table->string('codigo', 20)->nullable(false)->change();
            $table->unique('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('cupom_beneficios', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
            $table->dropColumn('codigo');
        });
    }
};
