<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\OrganizationSyncController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserManagementController;

// Публичные маршруты (для авторизации)
require __DIR__.'/auth.php';

// Защищенные маршруты
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:view-dashboard');
    
    // Управление пользователями (только для админов)
    Route::middleware(['permission:manage-users'])->group(function () {
        Route::get('/dashboard/users', [UserManagementController::class, 'index'])->name('dashboard.users');
        Route::patch('/dashboard/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('dashboard.users.update-role');
        Route::patch('/dashboard/users/{user}/permissions', [UserManagementController::class, 'updatePermissions'])->name('dashboard.users.update-permissions');
        Route::get('/dashboard/users/{user}/permissions', [UserManagementController::class, 'showPermissions'])->name('dashboard.users.permissions');
    });
    
    // Kanban - только с правом просмотра
    Route::get('/', [KanbanController::class, 'index'])
        ->name('kanban.index')
        ->middleware('permission:view-kanban');
    Route::get('/kanban', [KanbanController::class, 'index'])
        ->name('kanban.index')
        ->middleware('permission:view-kanban');
    
    // Обновление статуса задачи
    Route::patch('/tasks/{task}/status', [KanbanController::class, 'updateStatus'])
        ->name('tasks.update-status')
        ->middleware('permission:update-task-status');

    // Задачи - с проверкой прав
    Route::middleware(['permission:create-task'])->group(function () {
        Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    });
    
    Route::middleware(['permission:edit-task'])->group(function () {
        Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    });
    
    Route::middleware(['permission:delete-task'])->group(function () {
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    });

    // Синхронизация организации - для админов и редакторов
    Route::middleware(['permission:view-org-sync'])->group(function () {
        Route::get('/org-sync', [OrganizationSyncController::class, 'showInterface'])
            ->name('org-sync.interface');
        
        Route::get('/org-sync/test-mysql', [OrganizationSyncController::class, 'testMysqlOnly'])
            ->name('org-sync.test-mysql');
        
        Route::get('/org-sync/test', [OrganizationSyncController::class, 'testConnection'])
            ->name('org-sync.test');
    });
    
    Route::middleware(['permission:use-org-sync'])->group(function () {
        Route::get('/org-sync/sync', [OrganizationSyncController::class, 'sync'])
            ->name('org-sync.sync');
    });
});

Route::middleware(['auth'])->group(function () {
    // Временный маршрут для отладки разрешений
    Route::get('/debug-permissions', function () {
        $user = auth()->user();
        
        // Принудительно обновляем разрешения
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $user->refresh();
        $user->load('permissions', 'roles.permissions');
        
        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'roles' => $user->getRoleNames()->toArray(),
            'direct_permissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
            'all_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'permissions_check' => [
                'view-kanban' => $user->can('view-kanban'),
                'create-task' => $user->can('create-task'),
                'edit-task' => $user->can('edit-task'),
                'delete-task' => $user->can('delete-task'),
                'update-task-status' => $user->can('update-task-status'),
                'view-dashboard' => $user->can('view-dashboard'),
            ]
        ]);
    })->name('debug.permissions');
    
    // Остальные маршруты...
});