<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cria a pergunta "Cidade" como sistema se ainda não existir
        $exists = DB::table('questions')->where('role', 'city')->exists();

        if (!$exists) {
            $questionId = DB::table('questions')->insertGetId([
                'title'       => 'Cidade',
                'type'        => 'text',
                'is_required' => false,
                'is_unique'   => false,
                'is_active'   => true,
                'role'        => 'city',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Vincula a todos os tenants existentes
            $tenantIds = DB::table('tenants')->whereNull('deleted_at')->pluck('id');

            $rows = $tenantIds->map(fn ($id) => [
                'tenant_id'   => $id,
                'question_id' => $questionId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ])->all();

            if (!empty($rows)) {
                DB::table('tenant_questions')->insert($rows);
            }
        }
    }

    public function down(): void
    {
        $question = DB::table('questions')->where('role', 'city')->first();

        if ($question) {
            DB::table('tenant_questions')->where('question_id', $question->id)->delete();
            DB::table('questions')->where('id', $question->id)->delete();
        }
    }
};
