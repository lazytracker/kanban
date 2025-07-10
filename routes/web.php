<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\TaskController;

Route::get('/', [KanbanController::class, 'index'])->name('kanban.index');

Route::resource('tasks', TaskController::class)->except(['index', 'show']);

// Альтернативный способ определения маршрутов:
// Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
// Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
// Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
// Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
// Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');