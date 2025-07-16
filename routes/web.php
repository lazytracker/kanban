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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Управление пользователями (только для админов)
    Route::middleware(['permission:manage-users'])->group(function () {
        Route::get('/dashboard/users', [UserManagementController::class, 'index'])->name('dashboard.users');
        Route::patch('/dashboard/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('dashboard.users.update-role');
        Route::patch('/dashboard/users/{user}/permissions', [UserManagementController::class, 'updatePermissions'])->name('dashboard.users.update-permissions');
        Route::get('/dashboard/users/{user}/permissions', [UserManagementController::class, 'showPermissions'])->name('dashboard.users.permissions');
    });
    
    // Kanban - доступен всем авторизованным пользователям
    Route::get('/', [KanbanController::class, 'index'])->name('kanban.index');
    Route::get('/kanban', [KanbanController::class, 'index'])->name('kanban.index');
    Route::patch('/tasks/{task}/status', [KanbanController::class, 'updateStatus'])
        ->name('tasks.update-status')
        ->middleware('permission:update-task-status');

    // Задачи - с проверкой прав
    Route::resource('tasks', TaskController::class)->except(['index', 'show']);
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
    Route::middleware(['role:admin|editor'])->group(function () {
        Route::get('/org-sync', [OrganizationSyncController::class, 'showInterface'])
            ->name('org-sync.interface');
        
        Route::get('/org-sync/test-mysql', [OrganizationSyncController::class, 'testMysqlOnly'])
            ->name('org-sync.test-mysql');
        
        Route::get('/org-sync/test', [OrganizationSyncController::class, 'testConnection'])
            ->name('org-sync.test');
        
        Route::get('/org-sync/sync', [OrganizationSyncController::class, 'sync'])
            ->name('org-sync.sync');
    });
});