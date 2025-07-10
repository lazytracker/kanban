<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kanban Board</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <div class="container">
        <h1 class="board-title">Kanban Board</h1>
        
        <div class="kanban-board">
            <div class="kanban-column">
                <div class="column-header">
                    <h2 class="column-title">Задачи</h2>
                    <span class="task-count">4</span>
                </div>
                
                <div class="column-content">
                    <div class="kanban-card" data-priority="3">
                        <div class="card-header">
                            <span class="card-id">#001</span>
                            <span class="card-priority">3</span>
                        </div>
                        <h3 class="card-title">Реализовать аутентификацию пользователей</h3>
                        <p class="card-description">Создать систему регистрации и авторизации с использованием Laravel Sanctum</p>
                        <div class="card-footer">
                            <span class="card-date">10.07.2025</span>
                            <div class="card-tags">
                                <span class="tag tag-backend">Backend</span>
                                <span class="tag tag-auth">Auth</span>
                            </div>
                        </div>
                        <div class="card-assignees">
                            <div class="assignee-avatar" title="Алексей Петров">АП</div>
                            <div class="assignee-avatar" title="Мария Сидорова">МС</div>
                        </div>
                    </div>

                    <div class="kanban-card" data-priority="7">
                        <div class="card-header">
                            <span class="card-id">#002</span>
                            <span class="card-priority">7</span>
                        </div>
                        <h3 class="card-title">Создать API для управления задачами</h3>
                        <p class="card-description">Разработать RESTful API с полным CRUD функционалом</p>
                        <div class="card-footer">
                            <span class="card-date">12.07.2025</span>
                            <div class="card-tags">
                                <span class="tag tag-api">API</span>
                                <span class="tag tag-crud">CRUD</span>
                            </div>
                        </div>
                        <div class="card-assignees">
                            <div class="assignee-avatar" title="Иван Иванов">ИИ</div>
                        </div>
                    </div>

                    <div class="kanban-card" data-priority="10">
                        <div class="card-header">
                            <span class="card-id">#003</span>
                            <span class="card-priority">10</span>
                        </div>
                        <h3 class="card-title">Критическая ошибка безопасности</h3>
                        <p class="card-description">Исправить уязвимость в системе аутентификации, которая позволяет обход авторизации</p>
                        <div class="card-footer">
                            <span class="card-date">11.07.2025</span>
                            <div class="card-tags">
                                <span class="tag tag-security">Security</span>
                                <span class="tag tag-critical">Critical</span>
                            </div>
                        </div>
                        <div class="card-assignees">
                            <div class="assignee-avatar" title="Сергей Николаев">СН</div>
                            <div class="assignee-avatar" title="Анна Козлова">АК</div>
                        </div>
                    </div>

                    <div class="kanban-card" data-priority="1">
                        <div class="card-header">
                            <span class="card-id">#004</span>
                            <span class="card-priority">1</span>
                        </div>
                        <h3 class="card-title">Обновить документацию</h3>
                        <p class="card-description">Добавить описание новых функций в пользовательскую документацию</p>
                        <div class="card-footer">
                            <span class="card-date">20.07.2025</span>
                            <div class="card-tags">
                                <span class="tag tag-docs">Documentation</span>
                            </div>
                        </div>
                        <div class="card-assignees">
                            <div class="assignee-avatar" title="Ольга Федорова">ОФ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>