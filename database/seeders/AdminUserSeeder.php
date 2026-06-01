<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin'),
            ]
        );

        User::firstOrCreate(
            ['email' => 'junior@admin.com'],
            [
                'name' => 'Junior',
                'password' => Hash::make('admin'),
            ]
        );
    }
}
