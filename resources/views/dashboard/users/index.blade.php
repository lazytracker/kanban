<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="{{ asset('css/user_styles.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="users-container">
        <div class="page-header">
            <h1 class="page-title">Управление пользователями</h1>
            <a href="{{ route('dashboard') }}" class="back-link">
                ← Назад к панели управления
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <table class="users-table">
            <thead>
                <tr>
                    <th>Пользователь</th>
                    <th>Текущая роль</th>
                    <th>Дата регистрации</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div class="user-details">
                                    <h4>{{ $user->name }}</h4>
                                    <p>{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($user->roles->count() > 0)
                                @foreach($user->roles as $role)
                                    <span class="role-badge role-{{ $role->name }}">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            @else
                                <span class="role-badge" style="background: #6c757d; color: white;">
                                    Без роли
                                </span>
                            @endif
                        </td>
                        <td>{{ $user->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <div class="user-actions">
                                <div class="role-section">
                                    <select class="role-select" data-user-id="{{ $user->id }}">
                                        <option value="">Выберите роль</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}" 
                                                {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                                {{ ucfirst($role->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button class="btn-update" onclick="updateUserRole({{ $user->id }})">
                                        Обновить роль
                                    </button>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button class="btn-permissions" onclick="showPermissions({{ $user->id }})">
                                        Показать права
                                    </button>
                                    <button class="collapse-btn" onclick="togglePermissions({{ $user->id }})">
                                        Права доступа
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr id="permissions-row-{{ $user->id }}" class="permissions-row" style="display: none;">
                        <td colspan="4">
                            <div class="permissions-section">
                                <div class="permissions-header">
                                    <h4>Индивидуальные права доступа</h4>
                                    <button class="btn-save-permissions" onclick="saveUserPermissions({{ $user->id }})">
                                        Сохранить права
                                    </button>
                                </div>
                                <div class="permissions-grid" id="permissions-grid-{{ $user->id }}">
                                    @foreach($permissions as $permission)
                                        <div class="permission-item">
                                            <input type="checkbox" 
                                                   id="permission-{{ $user->id }}-{{ $permission->id }}" 
                                                   name="permissions[{{ $user->id }}][]" 
                                                   value="{{ $permission->name }}"
                                                   {{ $user->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                                            <label for="permission-{{ $user->id }}-{{ $permission->id }}">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Modal для отображения прав -->
    <div id="permissionModal" class="permission-modal">
        <div class="permission-modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Права пользователя</h3>
            <div id="permissionContent">
                <!-- Содержимое будет загружено через JavaScript -->
            </div>
        </div>
    </div>

    <script>
// Получаем CSRF токен
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Функция для обновления роли пользователя
function updateUserRole(userId) {
    const selectElement = document.querySelector(`select[data-user-id="${userId}"]`);
    const newRole = selectElement.value;
    
    if (!newRole) {
        showToast('Выберите роль', 'error');
        return;
    }

    fetch(`/dashboard/users/${userId}/role`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            role: newRole
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Роль успешно обновлена', 'success');
            // Обновляем отображение роли в таблице
            location.reload();
        } else {
            showToast('Ошибка при обновлении роли', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Ошибка при обновлении роли', 'error');
    });
}

// Функция для сохранения прав пользователя
function saveUserPermissions(userId) {
    const checkboxes = document.querySelectorAll(`input[name="permissions[${userId}][]"]`);
    const permissions = Array.from(checkboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);

    console.log('Отправляем права:', permissions); // Для отладки

    fetch(`/dashboard/users/${userId}/permissions`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            permissions: permissions
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Права успешно обновлены', 'success');
            // НЕ перезагружаем страницу - галочки остаются в том состоянии, в котором их выставил пользователь
        } else {
            showToast('Ошибка при обновлении прав', 'error');
            console.error('Server error:', data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Ошибка при обновлении прав', 'error');
    });
}

// Функция для переключения отображения прав
function togglePermissions(userId) {
    const row = document.getElementById(`permissions-row-${userId}`);
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
}

// Функция для показа прав пользователя в модальном окне
function showPermissions(userId) {
    fetch(`/dashboard/users/${userId}/permissions`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = document.getElementById('permissionModal');
            const content = document.getElementById('permissionContent');
            
            let html = '<h4>Роли:</h4>';
            if (data.roles.length > 0) {
                html += '<ul class="permission-list">';
                data.roles.forEach(role => {
                    html += `<li><strong>${role}</strong></li>`;
                });
                html += '</ul>';
            } else {
                html += '<p>Роли не назначены</p>';
            }
            
            html += '<h4>Права доступа:</h4>';
            if (data.permissions.length > 0) {
                html += '<ul class="permission-list">';
                data.permissions.forEach(permission => {
                    html += `<li>${permission}</li>`;
                });
                html += '</ul>';
            } else {
                html += '<p>Права доступа не назначены</p>';
            }
            
            content.innerHTML = html;
            modal.style.display = 'block';
        } else {
            showToast('Ошибка при загрузке прав', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Ошибка при загрузке прав', 'error');
    });
}

// Функция для закрытия модального окна
function closeModal() {
    document.getElementById('permissionModal').style.display = 'none';
}

// Закрытие модального окна при клике вне его
window.onclick = function(event) {
    const modal = document.getElementById('permissionModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Функция для показа уведомлений
function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}
    </script>
</body>
</html>