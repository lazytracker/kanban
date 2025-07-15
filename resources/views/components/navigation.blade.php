<nav class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex space-x-8">
                    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard*') ? 'text-blue-600 font-semibold' : '' }}">
                        Dashboard
                    </a>
                    
                    <a href="{{ route('kanban.index') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('kanban*') ? 'text-blue-600 font-semibold' : '' }}">
                        Kanban
                    </a>
                    
                    @can('create-task')
                        <a href="{{ route('tasks.create') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('tasks*') ? 'text-blue-600 font-semibold' : '' }}">
                            Создать задачу
                        </a>
                    @endcan
                    
                    @role('admin|editor')
                        <a href="{{ route('org-sync.interface') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('org-sync*') ? 'text-blue-600 font-semibold' : '' }}">
                            Синхронизация
                        </a>
                    @endrole
                    
                    @can('manage-users')
                        <a href="{{ route('dashboard.users') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard.users*') ? 'text-blue-600 font-semibold' : '' }}">
                            Пользователи
                        </a>
                    @endcan
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="text-sm text-gray-700">
                    <span class="font-medium">{{ Auth::user()->name }}</span>
                    @if(Auth::user()->roles->count() > 0)
                        <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                            {{ Auth::user()->roles->first()->name }}
                        </span>
                    @endif
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                        Выйти
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>