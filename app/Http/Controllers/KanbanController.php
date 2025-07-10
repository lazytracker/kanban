<?php
// app/Http/Controllers/KanbanController.php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

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
}