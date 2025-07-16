<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем права
        $permissions = [
            'view-kanban',
            'create-task',
            'edit-task',
            'delete-task',
            'update-task-status',
            'edit-self-task',
            'delete-self-task',
            'update-self-task-status',
            'view-org-sync',
            'use-org-sync',
            'manage-users',
            'view-dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Создаем роли
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $editorRole = Role::firstOrCreate(['name' => 'editor']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Назначаем права ролям (синхронизируем, чтобы убрать старые и добавить новые)
        $adminRole->syncPermissions(Permission::all());
        
        $editorRole->syncPermissions([
            'view-kanban',
            'create-task',
            'edit-task',
            'delete-task',
            'update-task-status',
            'edit-self-task',
            'delete-self-task',
            'update-self-task-status',
            'view-org-sync',
            'use-org-sync',
            'view-dashboard',
        ]);
        
        $userRole->syncPermissions([
            'view-kanban',
            'create-task',
            'edit-self-task',
            'delete-self-task',
            'update-self-task-status',
            'view-dashboard',
        ]);

        // Создаем админа (опционально)
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
            ]
        );
        
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}