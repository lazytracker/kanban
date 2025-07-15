@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h1 class="text-2xl font-bold mb-6">Dashboard</h1>
                
                <!-- Статистика -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-blue-800">Всего пользователей</h3>
                        <p class="text-2xl font-bold text-blue-600">{{ $stats['total_users'] }}</p>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-red-800">Администраторы</h3>
                        <p class="text-2xl font-bold text-red-600">{{ $stats['admins'] }}</p>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-yellow-800">Редакторы</h3>
                        <p class="text-2xl font-bold text-yellow-600">{{ $stats['editors'] }}</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-green-800">Пользователи</h3>
                        <p class="text-2xl font-bold text-green-600">{{ $stats['users'] }}</p>
                    </div>
                </div>

                <!-- Информация о пользователе -->
                <div class="bg-gray-50 p-6 rounded-lg mb-8">
                    <h2 class="text-xl font-semibold mb-4">Информация о вашем аккаунте</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p><strong>Имя:</strong> {{ auth()->user()->name }}</p>
                            <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
                        </div>
                        <div>
                            <p><strong>Роль:</strong> 
                                @if(auth()->user()->roles->count() > 0)
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                                        {{ auth()->user()->roles->first()->name }}
                                    </span>
                                @else
                                    <span class="text-gray-500">Роль не назначена</span>
                                @endif
                            </p>
                            <p><strong>Зарегистрирован:</strong> {{ auth()->user()->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Быстрые действия -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <a href="{{ route('kanban.index') }}" class="block p-6 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <h3 class="text-lg font-semibold text-blue-800 mb-2">Kanban доска</h3>
                        <p class="text-blue-600">Просмотреть и управлять задачами</p>
                    </a>
                    
                    @can('create-task')
                    <a href="{{ route('tasks.create') }}" class="block p-6 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                        <h3 class="text-lg font-semibold text-green-800 mb-2">Создать задачу</h3>
                        <p class="text-green-600">Добавить новую задачу в систему</p>
                    </a>
                    @endcan
                    
                    @can('manage-users')
                    <a href="{{ route('dashboard.users') }}" class="block p-6 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                        <h3 class="text-lg font-semibold text-purple-800 mb-2">Управление пользователями</h3>
                        <p class="text-purple-600">Управлять ролями пользователей</p>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection