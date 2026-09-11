<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin Default
        User::create([
            'name' => 'Super Admin',
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Operator Default
        User::create([
            'name' => 'Operator Stage',
            'username' => 'operator',
            'password' => Hash::make('operator123'),
            'role' => 'operator',
        ]);
    }
}