<?php
// app/Http/Controllers/KanbanController.php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\PermissionRegistrar;

class KanbanController extends Controller
{
    public function index()
    {
        // Принудительно обновляем кеш разрешений для текущего пользователя
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        
        // Или можно перезагрузить пользователя с разрешениями
        $user = auth()->user();
        $user->refresh();
        $user->load('permissions', 'roles.permissions');
        
        $tasks = Task::with(['organization', 'assignees'])
            ->orderBy('priority', 'desc')
            ->orderBy('completion_date', 'asc')
            ->get()
            ->groupBy('status');

        $organizations = Organization::all();
        $users = User::all();

        // Передаем права пользователя в представление
        $userPermissions = [
            'canCreateTask' => $user->can('create-task'),
            'canEditTask' => $user->can('edit-task'),
            'canDeleteTask' => $user->can('delete-task'),
            'canUpdateTaskStatus' => $user->can('update-task-status'),
            'canViewKanban' => $user->can('view-kanban'),
        ];

        // Логируем актуальные разрешения для отладки
        \Log::info('User permissions check', [
            'user_id' => $user->id,
            'permissions' => $userPermissions,
            'all_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'roles' => $user->getRoleNames()->toArray()
        ]);

        return view('kanban.index', compact('tasks', 'organizations', 'users', 'userPermissions'));
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        // Проверяем разрешение перед обновлением
        if (!auth()->user()->can('update-task-status')) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет прав для изменения статуса задач'
            ], 403);
        }

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