<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('questions')->whereIn('role', ['sexo', 'rg'])->exists();

        if (! $exists) {
            $sexoId = DB::table('questions')->insertGetId([
                'title' => 'Sexo',
                'type' => 'option',
                'options' => json_encode([
                    ['label' => 'Masculino', 'value' => 'M', 'order' => 0],
                    ['label' => 'Feminino', 'value' => 'F', 'order' => 1],
                ]),
                'is_required' => false,
                'is_unique' => false,
                'is_active' => true,
                'role' => 'sexo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rgId = DB::table('questions')->insertGetId([
                'title' => 'RG',
                'type' => 'text',
                'options' => null,
                'is_required' => false,
                'is_unique' => false,
                'is_active' => true,
                'role' => 'rg',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $tenantIds = DB::table('tenants')->whereNull('deleted_at')->pluck('id');

            $rows = $tenantIds->flatMap(fn ($id) => [
                ['tenant_id' => $id, 'question_id' => $sexoId, 'created_at' => now(), 'updated_at' => now()],
                ['tenant_id' => $id, 'question_id' => $rgId, 'created_at' => now(), 'updated_at' => now()],
            ])->all();

            if (! empty($rows)) {
                DB::table('tenant_questions')->insert($rows);
            }
        }
    }

    public function down(): void
    {
        foreach (['sexo', 'rg'] as $role) {
            $question = DB::table('questions')->where('role', $role)->first();

            if ($question) {
                DB::table('tenant_questions')->where('question_id', $question->id)->delete();
                DB::table('questions')->where('id', $question->id)->delete();
            }
        }
    }
};
