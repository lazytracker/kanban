<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kanban Board</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ asset('js/kanban.js') }}"></script>
</head>
<body>
    <div class="container">
        <div class="board-header">
            <h1 class="board-title">Kanban Board</h1>
            @can('create-task')
                <a href="{{ route('tasks.create') }}" class="btn btn-primary">+ Добавить задачу</a>
            @endcan
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        
        <div class="kanban-board">
            {{-- Колонка "Задачи" --}}
            <div class="kanban-column" data-status="todo">
                <div class="column-header">
                    <h2 class="column-title">Задачи</h2>
                    <span class="task-count">{{ $tasks->get('todo', collect())->count() }}</span>
                </div>
                
                <div class="column-content" data-status="todo">
                    @foreach($tasks->get('todo', collect()) as $task)
                        <div class="kanban-card" data-priority="{{ $task->priority }}" data-task-id="{{ $task->id }}" 
                             @if(auth()->user()->can('update-task-status') || (auth()->user()->can('update-self-task-status') && $task->isCreatedBy(auth()->user()))) 
                                 draggable="true" 
                             @endif>
                            <div class="card-header">
                                <span class="card-id">#{{ str_pad($task->id, 3, '0', STR_PAD_LEFT) }}</span>
                                <span class="card-priority priority-{{ $task->priority_color }}">{{ $task->priority }}</span>
                            </div>
                            <h3 class="card-title">{{ $task->title }}</h3>
                            @if($task->description)
                                <p class="card-description">{{ $task->description }}</p>
                            @endif
                            <div class="card-footer">
                                <span class="card-date">{{ $task->completion_date->format('d.m.Y') }}</span>
                                <div class="card-tags">
                                    <span class="tag tag-organization">{{ $task->organization->name }}</span>
                                </div>
                            </div>
                            @if($task->assignees->count() > 0)
                                <div class="card-assignees">
                                    @foreach($task->assignees as $assignee)
                                        <div class="assignee-avatar" title="{{ $assignee->name }}">
                                            {{ $assignee->initials }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            
                            {{-- Иконка создателя в правом нижнем углу --}}
                            @if($task->creator)
                                <div class="creator-avatar" title="Создал: {{ $task->creator->name }}">
                                    {{ $task->creator->initials }}
                                </div>
                            @endif
                            
                            @php
                                $canEdit = auth()->user()->can('edit-task') || (auth()->user()->can('edit-self-task') && $task->isCreatedBy(auth()->user()));
                                $canDelete = auth()->user()->can('delete-task') || (auth()->user()->can('delete-self-task') && $task->isCreatedBy(auth()->user()));
                            @endphp
                            
                            @if($canEdit || $canDelete)
                                <div class="card-actions">
                                    @if($canEdit)
                                        <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-edit">Изменить</a>
                                    @endif
                                    @if($canDelete)
                                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-delete" onclick="return confirm('Удалить задачу?')">Удалить</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Колонка "В процессе" --}}
            <div class="kanban-column" data-status="in_progress">
                <div class="column-header">
                    <h2 class="column-title">В процессе</h2>
                    <span class="task-count">{{ $tasks->get('in_progress', collect())->count() }}</span>
                </div>
                
                <div class="column-content" data-status="in_progress">
                    @foreach($tasks->get('in_progress', collect()) as $task)
                        <div class="kanban-card" data-priority="{{ $task->priority }}" data-task-id="{{ $task->id }}" 
                             @if(auth()->user()->can('update-task-status') || (auth()->user()->can('update-self-task-status') && $task->isCreatedBy(auth()->user()))) 
                                 draggable="true" 
                             @endif>
                            <div class="card-header">
                                <span class="card-id">#{{ str_pad($task->id, 3, '0', STR_PAD_LEFT) }}</span>
                                <span class="card-priority priority-{{ $task->priority_color }}">{{ $task->priority }}</span>
                            </div>
                            <h3 class="card-title">{{ $task->title }}</h3>
                            @if($task->description)
                                <p class="card-description">{{ $task->description }}</p>
                            @endif
                            <div class="card-footer">
                                <span class="card-date">{{ $task->completion_date->format('d.m.Y') }}</span>
                                <div class="card-tags">
                                    <span class="tag tag-organization">{{ $task->organization->name }}</span>
                                </div>
                            </div>
                            @if($task->assignees->count() > 0)
                                <div class="card-assignees">
                                    @foreach($task->assignees as $assignee)
                                        <div class="assignee-avatar" title="{{ $assignee->name }}">
                                            {{ $assignee->initials }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            
                            {{-- Иконка создателя в правом нижнем углу --}}
                            @if($task->creator)
                                <div class="creator-avatar" title="Создал: {{ $task->creator->name }}">
                                    {{ $task->creator->initials }}
                                </div>
                            @endif
                            
                            @php
                                $canEdit = auth()->user()->can('edit-task') || (auth()->user()->can('edit-self-task') && $task->isCreatedBy(auth()->user()));
                                $canDelete = auth()->user()->can('delete-task') || (auth()->user()->can('delete-self-task') && $task->isCreatedBy(auth()->user()));
                            @endphp
                            
                            @if($canEdit || $canDelete)
                                <div class="card-actions">
                                    @if($canEdit)
                                        <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-edit">Изменить</a>
                                    @endif
                                    @if($canDelete)
                                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-delete" onclick="return confirm('Удалить задачу?')">Удалить</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Колонка "Выполнено" --}}
            <div class="kanban-column" data-status="done">
                <div class="column-header">
                    <h2 class="column-title">Выполнено</h2>
                    <span class="task-count">{{ $tasks->get('done', collect())->count() }}</span>
                </div>
                
                <div class="column-content" data-status="done">
                    @foreach($tasks->get('done', collect()) as $task)
                        <div class="kanban-card" data-priority="{{ $task->priority }}" data-task-id="{{ $task->id }}" 
                             @if(auth()->user()->can('update-task-status') || (auth()->user()->can('update-self-task-status') && $task->isCreatedBy(auth()->user()))) 
                                 draggable="true" 
                             @endif>
                            <div class="card-header">
                                <span class="card-id">#{{ str_pad($task->id, 3, '0', STR_PAD_LEFT) }}</span>
                                <span class="card-priority priority-{{ $task->priority_color }}">{{ $task->priority }}</span>
                            </div>
                            <h3 class="card-title">{{ $task->title }}</h3>
                            @if($task->description)
                                <p class="card-description">{{ $task->description }}</p>
                            @endif
                            <div class="card-footer">
                                <span class="card-date">{{ $task->completion_date->format('d.m.Y') }}</span>
                                <div class="card-tags">
                                    <span class="tag tag-organization">{{ $task->organization->name }}</span>
                                </div>
                            </div>
                            @if($task->assignees->count() > 0)
                                <div class="card-assignees">
                                    @foreach($task->assignees as $assignee)
                                        <div class="assignee-avatar" title="{{ $assignee->name }}">
                                            {{ $assignee->initials }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            
                            {{-- Иконка создателя в правом нижнем углу --}}
                            @if($task->creator)
                                <div class="creator-avatar" title="Создал: {{ $task->creator->name }}">
                                    {{ $task->creator->initials }}
                                </div>
                            @endif
                            
                            @php
                                $canEdit = auth()->user()->can('edit-task') || (auth()->user()->can('edit-self-task') && $task->isCreatedBy(auth()->user()));
                                $canDelete = auth()->user()->can('delete-task') || (auth()->user()->can('delete-self-task') && $task->isCreatedBy(auth()->user()));
                            @endphp
                            
                            @if($canEdit || $canDelete)
                                <div class="card-actions">
                                    @if($canEdit)
                                        <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-edit">Изменить</a>
                                    @endif
                                    @if($canDelete)
                                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-delete" onclick="return confirm('Удалить задачу?')">Удалить</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
        // Передаем права пользователя в JavaScript
        window.userPermissions = @json($userPermissions);
    </script>
</body>
</html>