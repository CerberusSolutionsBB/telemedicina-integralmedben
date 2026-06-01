<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admintenant@admin.com'],
            [
                'name' => 'AdminTenant',
                'password' => Hash::make('admintenant'),
            ]
        );
    }
}
