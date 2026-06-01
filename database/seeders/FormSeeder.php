<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\User;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    public function run(): void
    {
        if (User::count() === 0) {
            $this->command->warn('Criando usuários primeiro...');
            User::factory(10)->create();
        }
        $totalUsers = User::count();
        $this->command->info("Total de usuários disponíveis: {$totalUsers}");
        $this->command->info('Criando formulários...');
        Form::factory()->count(25)->rascunho()->create();
        $this->command->info('✓ 25 rascunhos criados');
        Form::factory()->count(30)->ativo()->create();
        $this->command->info('✓ 30 ativos criados');
        Form::factory()->count(15)->pausado()->create();
        $this->command->info('✓ 15 pausados criados');
        Form::factory()->count(30)->encerrado()->create();
        $this->command->info('✓ 30 encerrados criados');
        $total = Form::count();
        $this->command->newLine();
        $this->command->info("✅ {$total} formulários criados com sucesso!");
        $stats = Form::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $this->command->newLine();
        $this->command->info('Distribuição:');
        foreach ($stats as $status => $count) {
            $this->command->line("  - {$status}: {$count}");
        }
    }
}
