<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\OrganizationSyncController;

Route::get('/', [KanbanController::class, 'index'])->name('kanban.index');

Route::resource('tasks', TaskController::class)->except(['index', 'show']);

Route::get('/org-sync', [OrganizationSyncController::class, 'showInterface'])
    ->name('org-sync.interface');

// Тест только MySQL
Route::get('/org-sync/test-mysql', [OrganizationSyncController::class, 'testMysqlOnly'])
    ->name('org-sync.test-mysql');

// Тест подключения
Route::get('/org-sync/test', [OrganizationSyncController::class, 'testConnection'])
    ->name('org-sync.test');

// Запуск синхронизации (используем GET для простоты)
Route::get('/org-sync/sync', [OrganizationSyncController::class, 'sync'])
    ->name('org-sync.sync');

    
// Альтернативный способ определения маршрутов:
// Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
// Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
// Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
// Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
// Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');