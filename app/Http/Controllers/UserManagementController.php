<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users with their roles.
     */
    public function index()
    {
        $users = User::with('roles', 'permissions')->orderBy('created_at', 'desc')->get();
        $roles = Role::all();
        $permissions = Permission::all();
        
        return view('dashboard.users.index', compact('users', 'roles', 'permissions'));
    }

    /**
     * Update user role.
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role' => 'required|string|exists:roles,name'
            ]);

            // Удаляем все текущие роли и назначаем новую
            $user->syncRoles([$validated['role']]);

            return response()->json([
                'success' => true,
                'message' => 'Роль пользователя успешно обновлена',
                'user_id' => $user->id,
                'new_role' => $validated['role']
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Неверные данные',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating user role: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при обновлении роли пользователя'
            ], 500);
        }
    }

    /**
     * Update user permissions.
     */
/**
 * Update user permissions.
 */
public function updatePermissions(Request $request, User $user): JsonResponse
{
    try {
        $validated = $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        // Логируем для отладки
        \Log::info('Updating permissions for user ' . $user->id, [
            'received_permissions' => $validated['permissions'] ?? [],
            'user_id' => $user->id
        ]);

        // Синхронизируем разрешения пользователя
        $user->syncPermissions($validated['permissions'] ?? []);

        // Очищаем кэш прав
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Проверяем, что права действительно назначены
        $actualPermissions = $user->permissions()->pluck('name')->toArray();
        
        \Log::info('Permissions after sync', [
            'actual_permissions' => $actualPermissions,
            'user_id' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Права пользователя успешно обновлены',
            'user_id' => $user->id,
            'permissions' => $validated['permissions'] ?? [],
            'actual_permissions' => $actualPermissions
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Неверные данные',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        \Log::error('Error updating user permissions: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Произошла ошибка при обновлении прав пользователя'
        ], 500);
    }
}

    /**
     * Show user permissions.
     */
    public function showPermissions(User $user): JsonResponse
    {
        $permissions = $user->getAllPermissions()->pluck('name');
        $roles = $user->getRoleNames();
        $allPermissions = Permission::all()->pluck('name');
        
        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'roles' => $roles,
            'permissions' => $permissions,
            'all_permissions' => $allPermissions
        ]);
    }
}