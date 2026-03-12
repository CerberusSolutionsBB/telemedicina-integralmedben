<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Atribui roles às perguntas de sistema existentes e garante que
     * todas as perguntas obrigatórias estejam vinculadas a todos os tenants.
     */
    public function up(): void
    {
        // Mapeia o título exato → role
        $roleMap = [
            'Nome Completo'       => 'nome',
            'E-mail'              => 'email',
            'Data de Nascimento'  => 'birth_date',
        ];

        foreach ($roleMap as $title => $role) {
            DB::table('questions')
                ->where('title', $title)
                ->whereNull('role')
                ->update(['role' => $role]);
        }

        // Sincroniza as perguntas de sistema recém-atribuídas
        // com todos os tenants que ainda não as possuem
        $systemQuestionIds = DB::table('questions')
            ->whereNotNull('role')
            ->whereNull('deleted_at')
            ->pluck('id');

        $tenantIds = DB::table('tenants')
            ->whereNull('deleted_at')
            ->pluck('id');

        $rows = [];
        foreach ($tenantIds as $tenantId) {
            $existing = DB::table('tenant_questions')
                ->where('tenant_id', $tenantId)
                ->pluck('question_id')
                ->toArray();

            foreach ($systemQuestionIds as $questionId) {
                if (!in_array($questionId, $existing)) {
                    $rows[] = [
                        'tenant_id'   => $tenantId,
                        'question_id' => $questionId,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }
            }
        }

        if (!empty($rows)) {
            DB::table('tenant_questions')->insert($rows);
        }
    }

    public function down(): void
    {
        DB::table('questions')
            ->whereIn('role', ['nome', 'email', 'birth_date'])
            ->update(['role' => null]);
    }
};
