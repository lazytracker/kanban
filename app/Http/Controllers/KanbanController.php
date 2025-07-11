<?php
// app/Http/Controllers/KanbanController.php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KanbanController extends Controller
{
    public function index()
    {
        $tasks = Task::with(['organization', 'assignees'])
            ->orderBy('priority', 'desc')
            ->orderBy('completion_date', 'asc')
            ->get()
            ->groupBy('status');

        $organizations = Organization::all();
        $users = User::all();

        return view('kanban.index', compact('tasks', 'organizations', 'users'));
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:todo,in_progress,done'
            ]);

            $task->update([
                'status' => $validated['status']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Статус задачи успешно обновлен',
                'task_id' => $task->id,
                'new_status' => $validated['status']
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Неверные данные',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating task status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при обновлении статуса задачи'
            ], 500);
        }
    }
}