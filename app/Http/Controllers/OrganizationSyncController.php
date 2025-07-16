<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrganizationSyncController extends Controller
{
    private $irbis;
    private $syncStats = [
        'total_records' => 0,
        'processed_records' => 0,
        'updated_records' => 0,
        'created_records' => 0,
        'skipped_records' => 0,
        'errors' => 0
    ];
    private $logMessages = [];

    public function sync(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        
        $startTime = microtime(true);

        try {
            Log::info('=== НАЧАЛО СИНХРОНИЗАЦИИ ОРГАНИЗАЦИЙ ===');
            
            $irbis_host = env('IRBIS_HOST', '127.0.0.1');
            $irbis_port = env('IRBIS_PORT', 6666);
            
            Log::info('Переменные окружения IRBIS:', [
                'IRBIS_HOST' => $irbis_host,
                'IRBIS_PORT' => $irbis_port,
                'IRBIS_USERNAME' => env('IRBIS_USERNAME', '1'),
                'IRBIS_DATABASE' => env('IRBIS_DATABASE', 'organization')
            ]);

            // Быстрая проверка сетевого подключения
            Log::info("Предварительная проверка сетевого подключения...");
            $socket_check = $this->checkSocket($irbis_host, $irbis_port);
            Log::info("Предварительная проверка сокета $irbis_host:$irbis_port: " . $socket_check['status'] . 
                     ($socket_check['error'] ? " (ошибка: {$socket_check['error']})" : ''));

            // Проверяем файл IRBIS
            $irbis_file = base_path('irbis_class.inc');
            Log::info("Проверка файла IRBIS: $irbis_file");
            
            if (!file_exists($irbis_file)) {
                throw new \Exception("Файл irbis_class.inc не найден по пути: " . $irbis_file);
            }
            
            Log::info("Размер файла IRBIS: " . filesize($irbis_file) . " байт");
            Log::info("Права доступа к файлу: " . substr(sprintf('%o', fileperms($irbis_file)), -4));

            // Подключаем IRBIS класс
            Log::info("Подключение файла irbis_class.inc...");
            require_once $irbis_file;
            Log::info("Файл irbis_class.inc подключен успешно");

            // Проверяем класс
            if (!class_exists('irbis64')) {
                throw new \Exception("Класс irbis64 не найден в файле irbis_class.inc");
            }
            Log::info("Класс irbis64 найден");

            // Создаем экземпляр класса
            Log::info("Создание экземпляра класса irbis64...");
            $this->irbis = new \irbis64(
                $irbis_host,
                $irbis_port,
                env('IRBIS_USERNAME', '1'),
                env('IRBIS_PASSWORD', '1'),
                env('IRBIS_DATABASE', 'organization')
            );
            Log::info("Экземпляр класса irbis64 создан");

            // Попытка подключения
            Log::info("Попытка подключения к IRBIS...");
            $start_time = microtime(true);
            $login_result = $this->irbis->login();
            $connection_time = round((microtime(true) - $start_time) * 1000, 2);
            
            Log::info("Результат login(): " . ($login_result ? 'true' : 'false'));
            Log::info("Время подключения: {$connection_time}мс");
            
            if (!$login_result) {
                $error_msg = $this->irbis->error();
                $error_code = method_exists($this->irbis, 'error_code') ? $this->irbis->error_code : 'undefined';
                
                Log::error("Ошибка подключения к IRBIS:", [
                    'error_message' => $error_msg,
                    'error_code' => $error_code,
                    'host' => $irbis_host,
                    'port' => $irbis_port,
                    'database' => env('IRBIS_DATABASE', 'organization'),
                    'connection_time_ms' => $connection_time
                ]);
                
                // Дополнительная диагностика при ошибке
                Log::info("Дополнительная сетевая диагностика при ошибке:");
                $this->performNetworkDiagnostics($irbis_host, $irbis_port);
                
                throw new \Exception("Ошибка подключения к базе ИРБИС: " . $error_msg . " (код: $error_code)");
            }

            $this->addLog("Подключение к базе ИРБИС organization установлено");
            Log::info("Подключение к IRBIS успешно установлено");

            // Получаем максимальный MFN
            Log::info("Получение максимального MFN...");
            $max_mfn = $this->irbis->mfn_max();
            Log::info("Максимальный MFN: " . ($max_mfn !== false ? $max_mfn : 'false'));
            
            if ($max_mfn === false) {
                $error_msg = $this->irbis->error();
                $error_code = method_exists($this->irbis, 'error_code') ? $this->irbis->error_code : 'undefined';
                
                Log::error("Ошибка получения максимального MFN:", [
                    'error_message' => $error_msg,
                    'error_code' => $error_code
                ]);
                
                throw new \Exception("Ошибка получения максимального MFN: " . $error_msg . " (код: $error_code)");
            }

            $this->syncStats['total_records'] = $max_mfn;
            $this->addLog("Найдено записей в базе organization: $max_mfn");

            Log::info("Начало обработки записей...");
            $this->processIrbisRecords($max_mfn);
            $this->logSyncStats();

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            Log::info("=== СИНХРОНИЗАЦИЯ ЗАВЕРШЕНА УСПЕШНО ===");
            Log::info("Время выполнения: {$executionTime}с");

            return response()->json([
                'success' => true,
                'message' => 'Синхронизация завершена успешно',
                'stats' => $this->syncStats,
                'log' => $this->logMessages,
                'execution_time' => $executionTime
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка синхронизации Organization:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addLog("ОШИБКА: " . $e->getMessage());
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'stats' => $this->syncStats,
                'log' => $this->logMessages,
                'execution_time' => $executionTime
            ], 500);
            
        } catch (\Error $e) {
            Log::error('Фатальная ошибка синхронизации:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addLog("ФАТАЛЬНАЯ ОШИБКА: " . $e->getMessage());
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            return response()->json([
                'success' => false,
                'message' => 'Фатальная ошибка: ' . $e->getMessage(),
                'stats' => $this->syncStats,
                'log' => $this->logMessages,
                'execution_time' => $executionTime
            ], 500);
            
        } catch (\Throwable $e) {
            Log::error('Непредвиденная ошибка синхронизации:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addLog("НЕПРЕДВИДЕННАЯ ОШИБКА: " . $e->getMessage());
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            return response()->json([
                'success' => false,
                'message' => 'Непредвиденная ошибка: ' . $e->getMessage(),
                'stats' => $this->syncStats,
                'log' => $this->logMessages,
                'execution_time' => $executionTime
            ], 500);
            
        } finally {
            if (isset($this->irbis)) {
                try {
                    Log::info("Закрытие соединения с IRBIS...");
                    $this->irbis->logout();
                    $this->addLog("Сессия ИРБИС завершена");
                    Log::info("Соединение с IRBIS закрыто");
                } catch (\Exception $e) {
                    Log::error("Ошибка при закрытии соединения IRBIS: " . $e->getMessage());
                }
            }
        }
    }

    private function processIrbisRecords($max_mfn)
    {
        Log::info("Начало обработки $max_mfn записей");
        
        for ($mfn = 1; $mfn <= $max_mfn; $mfn++) {
            try {
                $this->syncStats['processed_records']++;
                
                // Безопасное чтение записи
                $record = $this->irbis->record_read($mfn);
                
                if ($this->irbis->error_code != 0) {
                    if (in_array($this->irbis->error_code, [-603, -601, -140])) {
                        $this->syncStats['skipped_records']++;
                        
                        // Логируем пропущенные записи только каждую 100-ю
                        if ($mfn % 100 == 0) {
                            Log::debug("MFN $mfn пропущен (код ошибки: {$this->irbis->error_code})");
                        }
                        continue;
                    }
                    
                    Log::warning("Ошибка чтения записи MFN $mfn:", [
                        'error_code' => $this->irbis->error_code,
                        'error_message' => $this->irbis->error()
                    ]);
                    
                    $this->syncStats['errors']++;
                    continue;
                }

                // Извлекаем поля
                $field_800 = $this->getFieldValue($record, 800);
                $field_110 = $this->getFieldValue($record, 110);
                $field_11_1 = $this->getFieldValue($record, 11, 1);
                $field_11_2 = $this->getFieldValue($record, 11, 2);

                if (empty($field_800)) {
                    $this->syncStats['skipped_records']++;
                    
                    // Логируем только каждую 100-ю запись без field_800
                    if ($mfn % 100 == 0) {
                        Log::debug("MFN $mfn пропущен (отсутствует поле 800)");
                    }
                    continue;
                }

                $this->syncWithMysql($field_800, $field_110, $field_11_1, $field_11_2);

                // Логируем прогресс каждые 100 записей
                if ($mfn % 100 == 0) {
                    $progress = round(($mfn / $max_mfn) * 100, 1);
                    $this->addLog("Обработано: $mfn/$max_mfn ($progress%)");
                    Log::info("Прогресс обработки: $mfn/$max_mfn ($progress%)");
                }

            } catch (\Exception $e) {
                $this->syncStats['errors']++;
                
                Log::error("Ошибка обработки записи MFN $mfn:", [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                
                $this->addLog("Ошибка MFN=$mfn: " . $e->getMessage());
                continue;
            }
        }
        
        Log::info("Обработка записей завершена");
    }

    private function getFieldValue($record, $fieldTag, $occurrence = 1)
    {
        try {
            if (!$record || !is_object($record)) {
                return null;
            }
            
            $field = null;
            if (method_exists($record, 'getField')) {
                $field = $record->getField($fieldTag, $occurrence);
            } elseif (method_exists($record, 'field')) {
                $field = $record->field($fieldTag, $occurrence);
            }
            
            if (!$field) {
                return null;
            }
            
            if (is_array($field)) {
                if (isset($field['*'])) {
                    return trim((string)$field['*']);
                }
                if (!empty($field)) {
                    $firstValue = reset($field);
                    return trim((string)$firstValue);
                }
            }
            
            if (is_object($field)) {
                if (method_exists($field, 'toString')) {
                    return trim($field->toString());
                } elseif (method_exists($field, '__toString')) {
                    return trim((string)$field);
                }
            }
            
            return $field ? trim((string)$field) : null;
            
        } catch (\Exception $e) {
            Log::debug("Ошибка получения поля $fieldTag/$occurrence: " . $e->getMessage());
            return null;
        }
    }

    private function syncWithMysql($org_id, $name, $shortname1, $shortname2)
    {
        try {
            $organization = DB::table('organizations')->where('id', $org_id)->first();
            
            if ($organization) {
                // Обновляем
                DB::table('organizations')
                    ->where('id', $org_id)
                    ->update([
                        'name' => $name ?: '',
                        'shortname1' => $shortname1 ?: '',
                        'shortname2' => $shortname2 ?: '',
                        'updated_at' => now()
                    ]);
                
                $this->syncStats['updated_records']++;
                Log::debug("Обновлена организация ID: $org_id");
            } else {
                // Создаем
                DB::table('organizations')->insert([
                    'id' => $org_id,
                    'name' => $name ?: '',
                    'shortname1' => $shortname1 ?: '',
                    'shortname2' => $shortname2 ?: '',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $this->syncStats['created_records']++;
                Log::debug("Создана организация ID: $org_id");
            }

        } catch (\Exception $e) {
            $this->syncStats['errors']++;
            
            Log::error("Ошибка работы с MySQL для организации ID=$org_id:", [
                'message' => $e->getMessage(),
                'org_id' => $org_id,
                'name' => $name,
                'shortname1' => $shortname1,
                'shortname2' => $shortname2
            ]);
            
            throw new \Exception("Ошибка работы с MySQL для ID=$org_id: " . $e->getMessage());
        }
    }

    private function addLog($message)
    {
        $this->logMessages[] = $message;
        Log::info($message);
    }

    private function logSyncStats()
    {
        $stats = [
            'total_records' => $this->syncStats['total_records'],
            'processed_records' => $this->syncStats['processed_records'],
            'updated_records' => $this->syncStats['updated_records'],
            'created_records' => $this->syncStats['created_records'],
            'skipped_records' => $this->syncStats['skipped_records'],
            'errors' => $this->syncStats['errors']
        ];
        
        Log::info("Статистика синхронизации:", $stats);
        
        $this->addLog("=== СТАТИСТИКА СИНХРОНИЗАЦИИ ===");
        $this->addLog("Всего записей в ИРБИС: " . $this->syncStats['total_records']);
        $this->addLog("Обработано записей: " . $this->syncStats['processed_records']);
        $this->addLog("Обновлено в MySQL: " . $this->syncStats['updated_records']);
        $this->addLog("Создано в MySQL: " . $this->syncStats['created_records']);
        $this->addLog("Пропущено записей: " . $this->syncStats['skipped_records']);
        $this->addLog("Ошибок: " . $this->syncStats['errors']);
        $this->addLog("===============================");
    }

    public function showInterface()
    {
        return view('organization-sync');
    }

    public function testMysqlOnly()
    {
        try {
            Log::info("=== ТЕСТ MySQL ПОДКЛЮЧЕНИЯ ===");
            
            // Проверяем подключение к MySQL
            $orgs_count = DB::table('organizations')->count();
            
            Log::info("MySQL подключение успешно, количество организаций: $orgs_count");
            
            return response()->json([
                'success' => true,
                'mysql_connected' => true,
                'organizations_count' => $orgs_count,
                'message' => 'MySQL подключение работает'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка MySQL подключения:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function pingHost($host)
    {
        $command = "ping -c 1 -W 3 " . escapeshellarg($host);
        exec($command, $output, $return_code);
        return $return_code === 0;
    }

    private function checkPort($host, $port)
    {
        $command = "nc -z -w3 " . escapeshellarg($host) . " " . escapeshellarg($port);
        exec($command, $output, $return_code);
        return $return_code === 0;
    }

    private function checkSocket($host, $port)
    {
        $errno = 0;
        $errstr = '';
        $timeout = 5;
        
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        
        if ($socket) {
            fclose($socket);
            return ['status' => 'открыт', 'error' => null];
        } else {
            return ['status' => 'закрыт', 'error' => "$errno: $errstr"];
        }
    }

    private function performNetworkDiagnostics($host, $port)
    {
        // Проверяем netstat для активных соединений
        $netstat_command = "netstat -an | grep " . escapeshellarg($port);
        exec($netstat_command, $netstat_output);
        Log::info("Netstat для порта $port:", $netstat_output);
        
        // Проверяем telnet подключение
        $telnet_command = "timeout 5 telnet " . escapeshellarg($host) . " " . escapeshellarg($port) . " 2>&1";
        exec($telnet_command, $telnet_output);
        Log::info("Telnet $host:$port:", $telnet_output);
        
        // Проверяем доступность через curl (если порт поддерживает HTTP)
        if (function_exists('curl_init')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "http://$host:$port");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl, CURLOPT_NOBODY, true);
            
            $result = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);
            
            Log::info("Curl проверка $host:$port:", [
                'http_code' => $http_code,
                'error' => $error,
                'result' => $result !== false ? 'успешно' : 'неудачно'
            ]);
        }
        
        // Проверяем маршрутизацию
        $traceroute_command = "traceroute -m 5 " . escapeshellarg($host) . " 2>&1";
        exec($traceroute_command, $traceroute_output);
        Log::info("Traceroute до $host:", array_slice($traceroute_output, 0, 10)); // Только первые 10 строк
    }

    public function testConnection()
    {
        try {
            Log::info("=== ТЕСТ ПОДКЛЮЧЕНИЙ ===");
            
            // Проверяем системную информацию
            Log::info("Системная информация:", [
                'php_version' => PHP_VERSION,
                'os' => php_uname(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'loaded_extensions' => get_loaded_extensions()
            ]);
            
            // Проверяем сетевое подключение
            $irbis_host = env('IRBIS_HOST', '127.0.0.1');
            $irbis_port = env('IRBIS_PORT', 6666);
            
            Log::info("Проверка сетевого подключения к IRBIS:", [
                'host' => $irbis_host,
                'port' => $irbis_port
            ]);
            
            // Проверяем доступность хоста
            if ($irbis_host !== '127.0.0.1' && $irbis_host !== 'localhost') {
                $ping_result = $this->pingHost($irbis_host);
                Log::info("Результат ping $irbis_host: " . ($ping_result ? 'доступен' : 'недоступен'));
            }
            
            // Проверяем доступность порта
            $port_check = $this->checkPort($irbis_host, $irbis_port);
            Log::info("Проверка порта $irbis_host:$irbis_port: " . ($port_check ? 'открыт' : 'закрыт'));
            
            // Проверяем через fsockopen
            $socket_check = $this->checkSocket($irbis_host, $irbis_port);
            Log::info("Проверка сокета $irbis_host:$irbis_port: " . $socket_check['status'] . 
                     ($socket_check['error'] ? " (ошибка: {$socket_check['error']})" : ''));
            
            // Проверяем переменные окружения
            Log::info("Переменные окружения IRBIS:", [
                'IRBIS_HOST' => env('IRBIS_HOST', 'не задан'),
                'IRBIS_PORT' => env('IRBIS_PORT', 'не задан'),
                'IRBIS_USERNAME' => env('IRBIS_USERNAME', 'не задан'),
                'IRBIS_DATABASE' => env('IRBIS_DATABASE', 'не задан'),
                'APP_ENV' => env('APP_ENV', 'не задан'),
                'APP_DEBUG' => env('APP_DEBUG', 'не задан')
            ]);
            
            // Проверяем файл .env
            $env_file = base_path('.env');
            Log::info("Проверка файла .env:", [
                'path' => $env_file,
                'exists' => file_exists($env_file),
                'readable' => file_exists($env_file) ? is_readable($env_file) : false
            ]);
            
            // Проверяем значения напрямую из файла .env
            if (file_exists($env_file)) {
                $env_content = file_get_contents($env_file);
                $irbis_lines = [];
                foreach (explode("\n", $env_content) as $line) {
                    if (strpos($line, 'IRBIS_') === 0) {
                        $irbis_lines[] = $line;
                    }
                }
                Log::info("IRBIS переменные из .env файла:", $irbis_lines);
            }
            
            // Проверяем существование файла
            $irbis_file = base_path('irbis_class.inc');
            Log::info("Проверка файла IRBIS:", [
                'path' => $irbis_file,
                'exists' => file_exists($irbis_file),
                'readable' => is_readable($irbis_file),
                'size' => file_exists($irbis_file) ? filesize($irbis_file) : 0,
                'permissions' => file_exists($irbis_file) ? substr(sprintf('%o', fileperms($irbis_file)), -4) : 'н/д'
            ]);
            
            if (!file_exists($irbis_file)) {
                throw new \Exception("Файл irbis_class.inc не найден по пути: " . $irbis_file);
            }
            
            // Проверяем MySQL подключение
            try {
                $orgs_count = DB::table('organizations')->count();
                Log::info("MySQL подключение успешно, организаций: " . $orgs_count);
            } catch (\Exception $e) {
                Log::error("Ошибка MySQL подключения: " . $e->getMessage());
                throw new \Exception("Ошибка подключения к MySQL: " . $e->getMessage());
            }
            
            // Подключаем библиотеку ИРБИС
            Log::info("Подключение файла irbis_class.inc...");
            require_once $irbis_file;
            Log::info("Файл irbis_class.inc подключен");
            
            // Проверяем существование класса
            if (!class_exists('irbis64')) {
                Log::error("Класс irbis64 не найден");
                throw new \Exception("Класс irbis64 не найден в файле irbis_class.inc");
            }
            Log::info("Класс irbis64 найден");
            
            // Проверяем методы класса
            $class_methods = get_class_methods('irbis64');
            Log::info("Методы класса irbis64:", $class_methods);
            
            // Создаем подключение к ИРБИС
            Log::info("Создание экземпляра класса irbis64...");
            $irbis = new \irbis64(
                $irbis_host,
                $irbis_port,
                env('IRBIS_USERNAME', '1'),
                env('IRBIS_PASSWORD', '1'),
                env('IRBIS_DATABASE', 'organization')
            );
            Log::info("Экземпляр класса создан");
            
            // Проверяем методы экземпляра
            $instance_methods = get_class_methods($irbis);
            Log::info("Методы экземпляра irbis64:", $instance_methods);
            
            // Устанавливаем таймаут для подключения (если такой метод есть)
            if (method_exists($irbis, 'setTimeout')) {
                $irbis->setTimeout(10);
                Log::info("Установлен таймаут подключения: 10 секунд");
            }
            
            // Попытка подключения
            Log::info("Попытка подключения к IRBIS...");
            $start_time = microtime(true);
            $login_result = $irbis->login();
            $connection_time = round((microtime(true) - $start_time) * 1000, 2);
            
            Log::info("Результат подключения:", [
                'success' => $login_result ? 'успешно' : 'неудачно',
                'connection_time_ms' => $connection_time
            ]);
            
            if (!$login_result) {
                $error_msg = $irbis->error();
                $error_code = method_exists($irbis, 'error_code') ? $irbis->error_code : 'неопределён';
                
                // Дополнительная информация об ошибке
                $additional_info = [];
                if (method_exists($irbis, 'getLastError')) {
                    $additional_info['last_error'] = $irbis->getLastError();
                }
                if (method_exists($irbis, 'getConnectionInfo')) {
                    $additional_info['connection_info'] = $irbis->getConnectionInfo();
                }
                
                Log::error("Ошибка подключения к IRBIS:", [
                    'error_message' => $error_msg,
                    'error_code' => $error_code,
                    'host' => $irbis_host,
                    'port' => $irbis_port,
                    'database' => env('IRBIS_DATABASE', 'organization'),
                    'connection_time_ms' => $connection_time,
                    'additional_info' => $additional_info
                ]);
                
                // Дополнительная диагностика
                Log::info("Дополнительная сетевая диагностика:");
                $this->performNetworkDiagnostics($irbis_host, $irbis_port);
                
                throw new \Exception("Ошибка подключения к ИРБИС: " . $error_msg . " (код: $error_code)");
            }
            
            Log::info("Получение максимального MFN...");
            $max_mfn = $irbis->mfn_max();
            Log::info("Максимальный MFN: " . ($max_mfn !== false ? $max_mfn : 'ошибка'));
            
            Log::info("Закрытие соединения...");
            $irbis->logout();
            Log::info("Соединение закрыто");
            
            return response()->json([
                'success' => true,
                'irbis_connected' => true,
                'irbis_max_mfn' => $max_mfn,
                'mysql_connected' => true,
                'organizations_count' => $orgs_count,
                'message' => 'Все подключения работают'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка тестирования подключения:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
            
        } catch (\Error $e) {
            Log::error('Фатальная ошибка тестирования:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Фатальная ошибка: ' . $e->getMessage()
            ], 500);
            
        } catch (\Throwable $e) {
            Log::error('Непредвиденная ошибка тестирования:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Непредвиденная ошибка: ' . $e->getMessage()
            ], 500);
        }
    }
}