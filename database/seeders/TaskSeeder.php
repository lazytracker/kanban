<?php

namespace Database\Seeders;

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

        if ($organizations->isEmpty() || $users->isEmpty()) {
            $this->command->error('Организации или пользователи отсутствуют. Задачи не будут созданы.');
            return;
        }

        // Пример поиска по имени (или по email, если хочешь)
        $getUser = fn(string $name) => $users->firstWhere('name', $name)?->id;

        $task1 = Task::create([
            'organization_id' => $organizations->random()->id,
            'title' => 'Реализовать аутентификацию пользователей',
            'description' => 'Создать систему регистрации и авторизации с использованием Laravel Sanctum',
            'completion_date' => Carbon::now()->addDays(3),
            'priority' => 3,
            'status' => 'todo'
        ]);
        $task1->assignees()->attach([
            $getUser('Алексей Петров'),
            $getUser('Мария Сидорова'),
        ]);

        $task2 = Task::create([
            'organization_id' => $organizations->random()->id,
            'title' => 'Создать API для управления задачами',
            'description' => 'Разработать RESTful API с полным CRUD функционалом',
            'completion_date' => Carbon::now()->addDays(5),
            'priority' => 7,
            'status' => 'todo'
        ]);
        $task2->assignees()->attach([
            $getUser('Иван Иванов'),
        ]);

        $task3 = Task::create([
            'organization_id' => $organizations->random()->id,
            'title' => 'Критическая ошибка безопасности',
            'description' => 'Исправить уязвимость в системе аутентификации, которая позволяет обход авторизации',
            'completion_date' => Carbon::now()->addDays(1),
            'priority' => 10,
            'status' => 'in_progress'
        ]);
        $task3->assignees()->attach([
            $getUser('Сергей Николаев'),
            $getUser('Анна Козлова'),
        ]);

        $task4 = Task::create([
            'organization_id' => $organizations->random()->id,
            'title' => 'Обновить документацию',
            'description' => 'Добавить описание новых функций в пользовательскую документацию',
            'completion_date' => Carbon::now()->addDays(13),
            'priority' => 1,
            'status' => 'done'
        ]);
        $task4->assignees()->attach([
            $getUser('Ольга Федорова'),
        ]);

        $task5 = Task::create([
            'organization_id' => $organizations->random()->id,
            'title' => 'Интеграция с платежной системой',
            'description' => 'Подключить API банковской системы для обработки платежей',
            'completion_date' => Carbon::now()->addDays(7),
            'priority' => 8,
            'status' => 'todo'
        ]);
        $task5->assignees()->attach([
            $getUser('Алексей Петров'),
            $getUser('Иван Иванов'),
        ]);

        $task6 = Task::create([
            'organization_id' => $organizations->random()->id,
            'title' => 'Разработка мобильного приложения',
            'description' => 'Создать MVP мобильного приложения для iOS и Android',
            'completion_date' => Carbon::now()->addDays(30),
            'priority' => 6,
            'status' => 'in_progress'
        ]);
        $task6->assignees()->attach([
            $getUser('Мария Сидорова'),
            $getUser('Сергей Николаев'),
        ]);
    }
}
