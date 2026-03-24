<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@invoicedms.com'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
            ]
        );

        // Regular user
        User::firstOrCreate(
            ['email' => 'user@invoicedms.com'],
            [
                'name'     => 'Store User',
                'password' => Hash::make('user123'),
                'role'     => 'user',
            ]
        );
    }
}
