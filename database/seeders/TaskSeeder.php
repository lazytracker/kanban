<?php
// database/seeders/TaskSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Task;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;

class TaskSeeder extends Seeder
{
    public function run()
    {
        $organizations = Organization::all();
        $users = User::all();

        // Задача 1
        $task1 = Task::create([
            'organization_id' => $organizations->first()->id,
            'title' => 'Реализовать аутентификацию пользователей',
            'description' => 'Создать систему регистрации и авторизации с использованием Laravel Sanctum',
            'completion_date' => Carbon::now()->addDays(3),
            'priority' => 3,
            'status' => 'todo'
        ]);
        $task1->assignees()->attach([1, 2]); // Алексей Петров, Мария Сидорова

        // Задача 2
        $task2 = Task::create([
            'organization_id' => $organizations->first()->id,
            'title' => 'Создать API для управления задачами',
            'description' => 'Разработать RESTful API с полным CRUD функционалом',
            'completion_date' => Carbon::now()->addDays(5),
            'priority' => 7,
            'status' => 'todo'
        ]);
        $task2->assignees()->attach([3]); // Иван Иванов

        // Задача 3
        $task3 = Task::create([
            'organization_id' => $organizations->first()->id,
            'title' => 'Критическая ошибка безопасности',
            'description' => 'Исправить уязвимость в системе аутентификации, которая позволяет обход авторизации',
            'completion_date' => Carbon::now()->addDays(1),
            'priority' => 10,
            'status' => 'in_progress'
        ]);
        $task3->assignees()->attach([4, 5]); // Сергей Николаев, Анна Козлова

        // Задача 4
        $task4 = Task::create([
            'organization_id' => $organizations->first()->id,
            'title' => 'Обновить документацию',
            'description' => 'Добавить описание новых функций в пользовательскую документацию',
            'completion_date' => Carbon::now()->addDays(13),
            'priority' => 1,
            'status' => 'done'
        ]);
        $task4->assignees()->attach([6]); // Ольга Федорова

        // Дополнительные задачи для других организаций
        $task5 = Task::create([
            'organization_id' => $organizations->skip(1)->first()->id,
            'title' => 'Интеграция с платежной системой',
            'description' => 'Подключить API банковской системы для обработки платежей',
            'completion_date' => Carbon::now()->addDays(7),
            'priority' => 8,
            'status' => 'todo'
        ]);
        $task5->assignees()->attach([1, 3]);

        $task6 = Task::create([
            'organization_id' => $organizations->skip(2)->first()->id,
            'title' => 'Разработка мобильного приложения',
            'description' => 'Создать MVP мобильного приложения для iOS и Android',
            'completion_date' => Carbon::now()->addDays(30),
            'priority' => 6,
            'status' => 'in_progress'
        ]);
        $task6->assignees()->attach([2, 4]);
    }
}