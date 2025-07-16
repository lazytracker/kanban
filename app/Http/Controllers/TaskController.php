<?php
// app/Http/Controllers/TaskController.php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function create()
    {
        $organizations = Organization::all();
        $users = User::all();

        return view('tasks.create', compact('organizations', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'completion_date' => 'nullable|date|after:today',
            'indefinite_task' => 'nullable|boolean',
            'priority' => 'required|integer|min:1|max:10',
            'assignees' => 'array',
            'assignees.*' => 'exists:users,id'
        ]);

        // Проверяем логику даты завершения
        if (!$request->has('indefinite_task') || !$request->indefinite_task) {
            // Если задача не бессрочная, дата обязательна
            $request->validate([
                'completion_date' => 'required|date|after:today'
            ]);
        } else {
            // Если задача бессрочная, очищаем дату
            $validated['completion_date'] = null;
        }

        // Удаляем indefinite_task из validated данных, так как это поле не существует в таблице
        unset($validated['indefinite_task']);

        $task = Task::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        if ($request->has('assignees')) {
            $task->assignees()->attach($request->assignees);
        }

        return redirect()->route('kanban.index')
            ->with('success', 'Задача успешно создана!');
    }

    public function edit(Task $task)
    {
        $organizations = Organization::all();
        $users = User::all();

        return view('tasks.edit', compact('task', 'organizations', 'users'));
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'completion_date' => 'nullable|date|after:today',
            'indefinite_task' => 'nullable|boolean',
            'priority' => 'required|integer|min:1|max:10',
            'assignees' => 'array',
            'assignees.*' => 'exists:users,id'
        ]);

        // Проверяем логику даты завершения
        if (!$request->has('indefinite_task') || !$request->indefinite_task) {
            // Если задача не бессрочная, дата обязательна
            $request->validate([
                'completion_date' => 'required|date|after:today'
            ]);
        } else {
            // Если задача бессрочная, очищаем дату
            $validated['completion_date'] = null;
        }

        // Удаляем indefinite_task из validated данных, так как это поле не существует в таблице
        unset($validated['indefinite_task']);

        $task->update($validated);

        if ($request->has('assignees')) {
            $task->assignees()->sync($request->assignees);
        }

        return redirect()->route('kanban.index')
            ->with('success', 'Задача успешно обновлена!');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()->route('kanban.index')
            ->with('success', 'Задача успешно удалена!');
    }
}