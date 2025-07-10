<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать задачу</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1>Создать новую задачу</h1>
            <a href="{{ route('kanban.index') }}" class="btn btn-secondary">← Назад к доске</a>
        </div>

        <form method="POST" action="{{ route('tasks.store') }}" class="task-form">
            @csrf

            <div class="form-group">
                <label for="organization_id">Организация *</label>
                <select name="organization_id" id="organization_id" class="form-control" required>
                    <option value="">Выберите организацию</option>
                    @foreach($organizations as $organization)
                        <option value="{{ $organization->id }}" {{ old('organization_id') == $organization->id ? 'selected' : '' }}>
                            {{ $organization->name }}
                        </option>
                    @endforeach
                </select>
                @error('organization_id')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="title">Задача *</label>
                <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
                @error('title')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Описание</label>
                <textarea name="description" id="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                @error('description')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="completion_date">Дата завершения *</label>
                <input type="date" name="completion_date" id="completion_date" class="form-control" value="{{ old('completion_date') }}" required>
                @error('completion_date')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="priority">Приоритет *</label>
                <select name="priority" id="priority" class="form-control" required>
                    @for($i = 1; $i <= 10; $i++)
                        <option value="{{ $i }}" {{ old('priority') == $i ? 'selected' : '' }}>
                            {{ $i }} - {{ $i >= 8 ? 'Критический' : ($i >= 6 ? 'Высокий' : ($i >= 4 ? 'Средний' : 'Низкий')) }}
                        </option>
                    @endfor
                </select>
                @error('priority')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="assignees">Ответственные лица</label>
                <div class="assignees-list">
                    @foreach($users as $user)
                        <div class="assignee-item">
                            <input type="checkbox" name="assignees[]" value="{{ $user->id }}" 
                                   id="assignee_{{ $user->id }}" 
                                   {{ in_array($user->id, old('assignees', [])) ? 'checked' : '' }}>
                            <label for="assignee_{{ $user->id }}">{{ $user->name }}</label>
                        </div>
                    @endforeach
                </div>
                @error('assignees')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Создать задачу</button>
                <a href="{{ route('kanban.index') }}" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</body>
</html>