<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Алексей Петров',
            'email' => 'alexey.petrov@example.com',
            'password' => Hash::make('password')
        ]);

        User::create([
            'name' => 'Мария Сидорова',
            'email' => 'maria.sidorova@example.com',
            'password' => Hash::make('password')
        ]);

        User::create([
            'name' => 'Иван Иванов',
            'email' => 'ivan.ivanov@example.com',
            'password' => Hash::make('password')
        ]);

        User::create([
            'name' => 'Сергей Николаев',
            'email' => 'sergey.nikolaev@example.com',
            'password' => Hash::make('password')
        ]);

        User::create([
            'name' => 'Анна Козлова',
            'email' => 'anna.kozlova@example.com',
            'password' => Hash::make('password')
        ]);

        User::create([
            'name' => 'Ольга Федорова',
            'email' => 'olga.fedorova@example.com',
            'password' => Hash::make('password')
        ]);
    }
}