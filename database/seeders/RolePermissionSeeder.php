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
            'view-org-sync',
            'use-org-sync',
            'manage-users',
            'view-dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Создаем роли
        $adminRole = Role::create(['name' => 'admin']);
        $editorRole = Role::create(['name' => 'editor']);
        $userRole = Role::create(['name' => 'user']);

        // Назначаем права ролям
        $adminRole->givePermissionTo(Permission::all());
        
        $editorRole->givePermissionTo([
            'view-kanban',
            'create-task',
            'edit-task',
            'delete-task',
            'update-task-status',
            'view-org-sync',
            'use-org-sync',
            'view-dashboard',
        ]);
        
        $userRole->givePermissionTo([
            'view-kanban',
            'create-task',
            'edit-task',
            'update-task-status',
            'view-dashboard',
        ]);

        // Создаем админа (опционально)
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');
    }
}