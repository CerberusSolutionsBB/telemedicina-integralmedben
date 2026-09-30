<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_planos', function (Blueprint $table) {
            // Vagas restantes: decrementa a cada vínculo, volta ao desvincular.
            $table->integer('saldo')->default(0)->after('quantidade');
        });

        // Extrato de movimentos do saldo por tenant/plano.
        Schema::create('tenants_quantidade_parceiros', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('cod_plano');
            // Paciente (id no banco do tenant) que consumiu/devolveu a vaga.
            $table->unsignedBigInteger('parceiro_id')->nullable();
            $table->foreignId('telemedicina_tenant_id')->nullable()
                ->constrained('telemedicina_tenant')->nullOnDelete();
            $table->string('tipo'); // consumo | devolucao | ajuste
            $table->integer('variacao'); // -1 no consumo, +1 na devolução, ± no ajuste
            $table->integer('quantidade'); // saldo após o movimento
            $table->timestamps();

            $table->index(['tenant_id', 'cod_plano']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Saldo inicial = contratado - vínculos já existentes.
        DB::table('tenant_planos')->orderBy('id')->each(function ($plano) {
            $emUso = DB::table('telemedicina_tenant')
                ->where('tenant_id', $plano->tenant_id)
                ->whereNotNull('data->siprov_id')
                ->get(['data'])
                ->filter(function ($vinculo) use ($plano) {
                    $data = json_decode($vinculo->data, true) ?? [];
                    $codigos = $data['cod_planos'] ?? (isset($data['cod_plano']) ? [$data['cod_plano']] : []);

                    return in_array((string) $plano->cod_plano, array_map('strval', $codigos), true);
                })
                ->count();

            DB::table('tenant_planos')->where('id', $plano->id)->update(['saldo' => $plano->quantidade - $emUso]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants_quantidade_parceiros');

        Schema::table('tenant_planos', function (Blueprint $table) {
            $table->dropColumn('saldo');
        });
    }
};
