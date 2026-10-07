<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Usuários escolhidos para participar da meta (os perfis apenas agrupam).
        Schema::create('desempenho_user', function (Blueprint $table) {
            $table->foreignId('desempenho_id')->constrained('desempenhos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['desempenho_id', 'user_id']);
        });

        $this->vincularUsuariosDasMetasExistentes();
    }

    public function down(): void
    {
        Schema::dropIfExists('desempenho_user');
    }

    /**
     * Metas criadas antes da seleção individual: todos os usuários dos perfis
     * passam a constar como selecionados, mantendo os participantes atuais.
     */
    private function vincularUsuariosDasMetasExistentes(): void
    {
        DB::table('desempenho_role')->get()->groupBy('desempenho_id')->each(function ($roles, $desempenhoId) {
            $usuarios = DB::table('model_has_roles')
                ->whereIn('role_id', $roles->pluck('role_id'))
                ->where('model_type', User::class)
                ->distinct()
                ->pluck('model_id');

            if ($usuarios->isNotEmpty()) {
                DB::table('desempenho_user')->insert($usuarios->map(fn ($userId) => [
                    'desempenho_id' => $desempenhoId,
                    'user_id' => $userId,
                ])->all());
            }
        });
    }
};
