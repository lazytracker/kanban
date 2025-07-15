<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $stats = [
            'total_users' => User::count(),
            'admins' => User::role('admin')->count(),
            'editors' => User::role('editor')->count(),
            'users' => User::role('user')->count(),
        ];

        return view('dashboard.index', compact('stats'));
    }

    public function users()
    {
        $this->authorize('manage-users');
        
        $users = User::with('roles')->paginate(10);
        $roles = Role::all();
        
        return view('dashboard.users', compact('users', 'roles'));
    }

    public function updateUserRole(Request $request, User $user)
    {
        $this->authorize('manage-users');
        
        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        $user->syncRoles([$request->role]);

        return redirect()->back()->with('success', 'Роль пользователя обновлена успешно!');
    }
}