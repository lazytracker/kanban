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
                <label for="organization_search">Организация *</label>
                <div class="search-select">
                    <input type="text" 
                           id="organization_search" 
                           placeholder="Начните вводить название организации..."
                           autocomplete="off">
                    <input type="hidden" name="organization_id" id="organization_id" value="{{ old('organization_id') }}">
                    <div class="dropdown" id="organization_dropdown">
                        <!-- Опции будут добавлены через JavaScript -->
                    </div>
                </div>
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
                <div class="date-group" id="date_group">
                    <div class="date-input-wrapper">
                        <input type="date" name="completion_date" id="completion_date" class="form-control" value="{{ old('completion_date') }}" lang="ru">
                        <div class="calendar-icon" id="calendar_icon">📅</div>
                    </div>
                </div>
                <div class="indefinite-option">
                    <input type="checkbox" id="indefinite_task" name="indefinite_task" value="1" 
                           {{ old('indefinite_task') ? 'checked' : '' }}>
                    <label for="indefinite_task">Бессрочно</label>
                </div>
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

    <script>
        // Данные организаций (в реальном приложении получайте через AJAX)
        const organizations = {!! json_encode($organizations->map(function($org) {
            return [
                'id' => $org->id,
                'name' => $org->name,
                'shortname1' => $org->shortname1 ?? '',
                'shortname2' => $org->shortname2 ?? ''
            ];
        })) !!};

        // Поисковый выпадающий список организаций
        const searchInput = document.getElementById('organization_search');
        const hiddenInput = document.getElementById('organization_id');
        const dropdown = document.getElementById('organization_dropdown');
        let selectedIndex = -1;

        // Инициализация выбранной организации
        if (hiddenInput.value) {
            const selectedOrg = organizations.find(org => org.id == hiddenInput.value);
            if (selectedOrg) {
                searchInput.value = selectedOrg.name;
            }
        }

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            if (query.length === 0) {
                dropdown.classList.remove('show');
                hiddenInput.value = '';
                return;
            }

            const filtered = organizations.filter(org => {
                return org.name.toLowerCase().includes(query) ||
                       org.shortname1.toLowerCase().includes(query) ||
                       org.shortname2.toLowerCase().includes(query);
            });

            displayResults(filtered);
            selectedIndex = -1;
        });

        searchInput.addEventListener('focus', function() {
            if (this.value) {
                searchInput.dispatchEvent(new Event('input'));
            }
        });

        searchInput.addEventListener('keydown', function(e) {
            const items = dropdown.querySelectorAll('.dropdown-item:not(.no-results)');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    selectOrganization(items[selectedIndex]);
                }
            } else if (e.key === 'Escape') {
                dropdown.classList.remove('show');
                selectedIndex = -1;
            }
        });

        function displayResults(filtered) {
            dropdown.innerHTML = '';
            
            if (filtered.length === 0) {
                dropdown.innerHTML = '<div class="no-results">Организации не найдены</div>';
            } else {
                // Показываем максимум 10 результатов
                const limitedResults = filtered.slice(0, 10);
                limitedResults.forEach(org => {
                    const item = document.createElement('div');
                    item.className = 'dropdown-item';
                    item.textContent = org.name;
                    item.addEventListener('click', () => selectOrganization(item));
                    item.dataset.orgId = org.id;
                    dropdown.appendChild(item);
                });
            }
            
            dropdown.classList.add('show');
        }

        function updateSelection(items) {
            items.forEach((item, index) => {
                item.classList.toggle('selected', index === selectedIndex);
            });
        }

        function selectOrganization(item) {
            const orgId = item.dataset.orgId;
            const orgName = item.textContent;
            
            searchInput.value = orgName;
            hiddenInput.value = orgId;
            dropdown.classList.remove('show');
            selectedIndex = -1;
        }

        // Закрытие выпадающего списка при клике вне его
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-select')) {
                dropdown.classList.remove('show');
                selectedIndex = -1;
            }
        });

        // Функционал для опции "бессрочно"
        const indefiniteCheckbox = document.getElementById('indefinite_task');
        const dateGroup = document.getElementById('date_group');
        const completionDateInput = document.getElementById('completion_date');

        indefiniteCheckbox.addEventListener('change', function() {
            if (this.checked) {
                dateGroup.classList.add('disabled');
                completionDateInput.removeAttribute('required');
                completionDateInput.value = '';
            } else {
                dateGroup.classList.remove('disabled');
                completionDateInput.setAttribute('required', 'required');
            }
        });

        // Инициализация состояния при загрузке
        if (indefiniteCheckbox.checked) {
            dateGroup.classList.add('disabled');
            completionDateInput.removeAttribute('required');
        }

        // Принудительное форматирование даты в европейском формате
        completionDateInput.addEventListener('input', function(e) {
            // Дополнительная обработка для консистентного отображения может быть добавлена здесь
        });

        // Функционал календаря
        const dateInputWrapper = document.querySelector('.date-input-wrapper');
        const calendarIcon = document.getElementById('calendar_icon');

        // Переменная для отслеживания, был ли клик по input'у
        let isInputClick = false;

        // Клик по иконке календаря - всегда открывает календарь
        calendarIcon.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (!dateGroup.classList.contains('disabled')) {
                completionDateInput.focus();
                setTimeout(() => {
                    if (completionDateInput.showPicker) {
                        completionDateInput.showPicker();
                    }
                }, 10);
            }
        });

        // Клик по wrapper'у - открывает календарь, если не кликнули по input'у
        dateInputWrapper.addEventListener('click', function(e) {
            if (!isInputClick && e.target !== completionDateInput && !dateGroup.classList.contains('disabled')) {
                completionDateInput.focus();
                setTimeout(() => {
                    if (completionDateInput.showPicker) {
                        completionDateInput.showPicker();
                    }
                }, 10);
            }
            isInputClick = false;
        });

        // Клик по самому input'у - помечаем, что это клик по input'у
        completionDateInput.addEventListener('mousedown', function(e) {
            isInputClick = true;
        });

        // Дополнительная обработка для открытия календаря при клике в области input'а
        completionDateInput.addEventListener('click', function(e) {
            if (!dateGroup.classList.contains('disabled')) {
                const rect = this.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const inputWidth = rect.width;
                
                // Если клик в правых 70% поля - открываем календарь
                if (clickX > inputWidth * 0.3) {
                    setTimeout(() => {
                        if (completionDateInput.showPicker) {
                            completionDateInput.showPicker();
                        }
                    }, 10);
                }
            }
        });

        // Двойной клик по input'у - открывает календарь
        completionDateInput.addEventListener('dblclick', function(e) {
            if (!dateGroup.classList.contains('disabled')) {
                setTimeout(() => {
                    if (completionDateInput.showPicker) {
                        completionDateInput.showPicker();
                    }
                }, 10);
            }
        });
    </script>
</body>
</html>