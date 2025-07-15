@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">Управление пользователями</h1>
                    <a href="{{ route('dashboard') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Назад к Dashboard
                    </a>
                </div>

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 border-b border-gray-200 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                    Пользователь
                                </th>
                                <th class="px-6 py-3 border-b border-gray-200 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                    Email
                                </th>
                                <th class="px-6 py-3 border-b border-gray-200 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                    Текущая роль
                                </th>
                                <th class="px-6 py-3 border-b border-gray-200 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                    Изменить роль
                                </th>
                                <th class="px-6 py-3 border-b border-gray-200 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                    Дата регистрации
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($users as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-no-wrap">
                                    <div class="text-sm leading-5 font-medium text-gray-900">
                                        {{ $user->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-no-wrap">
                                    <div class="text-sm leading-5 text-gray-900">
                                        {{ $user->email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-no-wrap">
                                    @if($user->roles->count() > 0)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($user->roles->first()->name == 'admin') bg-red-100 text-red-800
                                            @elseif($user->roles->first()->name == 'editor') bg-yellow-100 text-yellow-800
                                            @else bg-green-100 text-green-800
                                            @endif">
                                            {{ $user->roles->first()->name }}
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Без роли
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-no-wrap">
                                    <form action="{{ route('dashboard.users.update-role', $user) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="border rounded px-2 py-1 text-sm" onchange="this.form.submit()">
                                            <option value="">Выберите роль</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->name }}" 
                                                    {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                                    {{ $role->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-900">
                                    {{ $user->created_at->format('d.m.Y H:i') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection