<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'], // cari kalau sudah ada
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'), // password default
            ]
        );
    }
}
