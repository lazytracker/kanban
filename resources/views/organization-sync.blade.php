<!DOCTYPE html>
<html>
<head>
    <title>Синхронизация Организаций</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .btn { padding: 10px 15px; margin: 5px; border: none; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .log { background: #f8f9fa; padding: 10px; margin: 10px 0; height: 300px; overflow-y: auto; border: 1px solid #ddd; }
        .stats { background: #e9ecef; padding: 10px; margin: 10px 0; }
        .status { margin: 10px 0; padding: 10px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .loading { color: #856404; }
    </style>
</head>
<body>
    <h1>Синхронизация Организаций (ИРБИС → MySQL)</h1>
    
    <div>
        <button class="btn btn-info" onclick="testMysqlOnly()">Тест MySQL</button>
        <button class="btn btn-info" onclick="testConnection()">Тест подключения</button>
        <button class="btn btn-primary" onclick="startSync()">Начать синхронизацию</button>
    </div>
    
    <div id="status" class="status"></div>
    
    <div id="stats" class="stats" style="display: none;">
        <h3>Статистика</h3>
        <div id="statsContent"></div>
    </div>
    
    <div class="log">
        <h3>Лог выполнения</h3>
        <div id="logContent"></div>
    </div>

    <script>
        function testMysqlOnly() {
            setStatus('Тестирование MySQL...', 'loading');
            clearLog();
            
            fetch('/org-sync/test-mysql')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            setStatus('MySQL подключение успешно', 'success');
                            addLog('MySQL: организаций в базе: ' + data.organizations_count);
                        } else {
                            setStatus('Ошибка MySQL: ' + data.error, 'error');
                        }
                    } catch (e) {
                        setStatus('Ошибка парсинга ответа', 'error');
                        addLog('Ответ сервера: ' + text);
                    }
                })
                .catch(error => {
                    setStatus('Ошибка: ' + error.message, 'error');
                });
        }

        function testConnection() {
            setStatus('Тестирование подключения...', 'loading');
            clearLog();
            
            fetch('/org-sync/test')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            setStatus('Подключение успешно', 'success');
                            addLog('ИРБИС: подключено, записей: ' + data.irbis_max_mfn);
                            addLog('MySQL: подключено, организаций: ' + data.organizations_count);
                        } else {
                            setStatus('Ошибка подключения: ' + data.error, 'error');
                        }
                    } catch (e) {
                        setStatus('Ошибка сервера', 'error');
                        addLog('Ответ сервера: ' + text);
                    }
                })
                .catch(error => {
                    setStatus('Ошибка: ' + error.message, 'error');
                });
        }
        
        function startSync() {
            setStatus('Выполняется синхронизация...', 'loading');
            clearLog();
            hideStats();
            
            fetch('/org-sync/sync')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            setStatus('Синхронизация завершена успешно', 'success');
                            showStats(data.stats);
                        } else {
                            setStatus('Ошибка синхронизации: ' + data.message, 'error');
                        }
                        
                        if (data.log) {
                            data.log.forEach(message => addLog(message));
                        }
                    } catch (e) {
                        setStatus('Ошибка сервера', 'error');
                        addLog('Ответ сервера: ' + text);
                    }
                })
                .catch(error => {
                    setStatus('Ошибка: ' + error.message, 'error');
                });
        }
        
        function setStatus(message, type) {
            const statusDiv = document.getElementById('status');
            statusDiv.textContent = message;
            statusDiv.className = 'status ' + type;
        }
        
        function addLog(message) {
            const logDiv = document.getElementById('logContent');
            const timestamp = new Date().toLocaleTimeString();
            logDiv.innerHTML += '[' + timestamp + '] ' + message + '<br>';
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        function clearLog() {
            document.getElementById('logContent').innerHTML = '';
        }
        
        function showStats(stats) {
            const statsDiv = document.getElementById('stats');
            const statsContent = document.getElementById('statsContent');
            
            statsContent.innerHTML = `
                <p><strong>Всего записей:</strong> ${stats.total_records}</p>
                <p><strong>Обработано:</strong> ${stats.processed_records}</p>
                <p><strong>Обновлено:</strong> ${stats.updated_records}</p>
                <p><strong>Создано:</strong> ${stats.created_records}</p>
                <p><strong>Пропущено:</strong> ${stats.skipped_records}</p>
                <p><strong>Ошибок:</strong> ${stats.errors}</p>
            `;
            
            statsDiv.style.display = 'block';
        }
        
        function hideStats() {
            document.getElementById('stats').style.display = 'none';
        }
    </script>
</body>
</html>