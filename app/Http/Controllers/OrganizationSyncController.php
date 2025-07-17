<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

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

    /**
     * Получение конфигурации IRBIS
     */
    private function getIrbisConfig()
    {
        return Config::get('irbis', [
            'host' => '127.0.0.1',
            'port' => 6666,
            'username' => '1',
            'password' => '1',
            'database' => 'organization'
        ]);
    }

    public function sync(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        
        $startTime = microtime(true);

        try {
            // Подключаем IRBIS класс
            require_once base_path('irbis_class.inc');

            $irbisConfig = $this->getIrbisConfig();

            $this->irbis = new \irbis64(
                $irbisConfig['host'],
                $irbisConfig['port'],
                $irbisConfig['username'],
                $irbisConfig['password'],
                $irbisConfig['database']
            );

            if (!$this->irbis->login()) {
                throw new \Exception("Ошибка подключения к базе ИРБИС: " . $this->irbis->error());
            }

            $this->addLog("Подключение к базе ИРБИС organization установлено");

            $max_mfn = $this->irbis->mfn_max();
            if ($max_mfn === false) {
                throw new \Exception("Ошибка получения максимального MFN: " . $this->irbis->error());
            }

            $this->syncStats['total_records'] = $max_mfn;
            $this->addLog("Найдено записей в базе organization: $max_mfn");

            $this->processIrbisRecords($max_mfn);
            $this->logSyncStats();

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            return response()->json([
                'success' => true,
                'message' => 'Синхронизация завершена успешно',
                'stats' => $this->syncStats,
                'log' => $this->logMessages,
                'execution_time' => $executionTime
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка синхронизации Organization: ' . $e->getMessage());
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
            
        } finally {
            if (isset($this->irbis)) {
                $this->irbis->logout();
                $this->addLog("Сессия ИРБИС завершена");
            }
        }
    }

    private function processIrbisRecords($max_mfn)
    {
        for ($mfn = 1; $mfn <= $max_mfn; $mfn++) {
            try {
                $this->syncStats['processed_records']++;
                
                // Безопасное чтение записи (как в рабочем коде)
                $record = $this->irbis->record_read($mfn);
                
                if ($this->irbis->error_code != 0) {
                    if (in_array($this->irbis->error_code, [-603, -601, -140])) {
                        $this->syncStats['skipped_records']++;
                        continue;
                    }
                    $this->syncStats['errors']++;
                    continue;
                }

                // Извлекаем поля (используем ту же логику что в рабочем коде)
                $field_800 = $this->getFieldValue($record, 800);
                $field_110 = $this->getFieldValue($record, 110);
                $field_11_1 = $this->getFieldValue($record, 11, 1);
                $field_11_2 = $this->getFieldValue($record, 11, 2);

                if (empty($field_800)) {
                    $this->syncStats['skipped_records']++;
                    continue;
                }

                $this->syncWithMysql($field_800, $field_110, $field_11_1, $field_11_2);

                // Логируем прогресс каждые 100 записей
                if ($mfn % 100 == 0) {
                    $progress = round(($mfn / $max_mfn) * 100, 1);
                    $this->addLog("Обработано: $mfn/$max_mfn ($progress%)");
                }

            } catch (\Exception $e) {
                $this->syncStats['errors']++;
                $this->addLog("Ошибка MFN=$mfn: " . $e->getMessage());
                continue;
            }
        }
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
            }

        } catch (\Exception $e) {
            $this->syncStats['errors']++;
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
            // Проверяем подключение к MySQL
            $orgs_count = DB::table('organizations')->count();
            
            return response()->json([
                'success' => true,
                'mysql_connected' => true,
                'organizations_count' => $orgs_count,
                'message' => 'MySQL подключение работает'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка MySQL: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testConnection()
    {
        $logContext = ['method' => 'testConnection', 'step' => 'start'];
        
        try {
            Log::info('=== НАЧАЛО ТЕСТИРОВАНИЯ ПОДКЛЮЧЕНИЯ ===', $logContext);
            
            // 1. Проверяем конфигурацию IRBIS
            $logContext['step'] = 'config_check';
            Log::info('Проверка конфигурации IRBIS:', $logContext);
            
            $irbisConfig = $this->getIrbisConfig();
            
            Log::info("IRBIS_HOST = " . $irbisConfig['host'], $logContext);
            Log::info("IRBIS_PORT = " . $irbisConfig['port'], $logContext);
            Log::info("IRBIS_USERNAME = " . $irbisConfig['username'], $logContext);
            Log::info("IRBIS_PASSWORD = [СКРЫТО]", $logContext);
            Log::info("IRBIS_DATABASE = " . $irbisConfig['database'], $logContext);
            
            // 2. Проверяем существование файла IRBIS
            $logContext['step'] = 'irbis_file_check';
            Log::info('Проверка файла IRBIS:', $logContext);
            
            $irbis_file = base_path('irbis_class.inc');
            Log::info("Путь к файлу IRBIS: $irbis_file", $logContext);
            Log::info("Файл IRBIS существует: " . (file_exists($irbis_file) ? 'ДА' : 'НЕТ'), $logContext);
            
            if (!file_exists($irbis_file)) {
                // Проверяем альтернативные пути
                $alternative_paths = [
                    base_path('app/irbis_class.inc'),
                    base_path('resources/irbis_class.inc'),
                    base_path('storage/irbis_class.inc'),
                ];
                
                foreach ($alternative_paths as $alt_path) {
                    Log::info("Проверка альтернативного пути: $alt_path - " . (file_exists($alt_path) ? 'НАЙДЕН' : 'НЕ НАЙДЕН'), $logContext);
                }
                
                throw new \Exception("Файл irbis_class.inc не найден по пути: " . $irbis_file);
            }
            
            Log::info("Размер файла IRBIS: " . filesize($irbis_file) . " байт", $logContext);
            Log::info("Права на файл IRBIS: " . substr(sprintf('%o', fileperms($irbis_file)), -4), $logContext);
            
            // 3. Проверяем MySQL подключение
            $logContext['step'] = 'mysql_check';
            Log::info('Проверка MySQL подключения:', $logContext);
            
            try {
                $orgs_count = DB::table('organizations')->count();
                Log::info("MySQL подключение успешно, организаций: " . $orgs_count, $logContext);
            } catch (\Exception $e) {
                Log::error("Ошибка подключения к MySQL: " . $e->getMessage(), $logContext);
                throw new \Exception("Ошибка подключения к MySQL: " . $e->getMessage());
            }
            
            // 4. Подключаем библиотеку ИРБИС
            $logContext['step'] = 'irbis_include';
            Log::info('Подключение библиотеки IRBIS:', $logContext);
            
            try {
                require_once $irbis_file;
                Log::info("Файл irbis_class.inc успешно подключен", $logContext);
            } catch (\Exception $e) {
                Log::error("Ошибка подключения файла IRBIS: " . $e->getMessage(), $logContext);
                throw new \Exception("Ошибка подключения файла IRBIS: " . $e->getMessage());
            }
            
            // 5. Проверяем существование класса
            $logContext['step'] = 'class_check';
            Log::info('Проверка класса irbis64:', $logContext);
            
            if (!class_exists('irbis64')) {
                Log::error("Класс irbis64 не найден в файле irbis_class.inc", $logContext);
                
                // Попробуем получить список всех определенных классов
                $defined_classes = get_declared_classes();
                $irbis_classes = array_filter($defined_classes, function($class) {
                    return stripos($class, 'irbis') !== false;
                });
                
                if (!empty($irbis_classes)) {
                    Log::info("Найдены IRBIS-подобные классы: " . implode(', ', $irbis_classes), $logContext);
                } else {
                    Log::info("IRBIS-подобные классы не найдены", $logContext);
                }
                
                throw new \Exception("Класс irbis64 не найден в файле irbis_class.inc");
            }
            
            Log::info("Класс irbis64 найден успешно", $logContext);
            
            // 6. Создаем подключение к ИРБИС
            $logContext['step'] = 'irbis_connection';
            Log::info('Создание подключения к ИРБИС:', $logContext);
            
            Log::info("Параметры подключения IRBIS:", array_merge($logContext, [
                'host' => $irbisConfig['host'],
                'port' => $irbisConfig['port'],
                'username' => $irbisConfig['username'],
                'password' => '[СКРЫТО]',
                'database' => $irbisConfig['database']
            ]));
            
            // Проверяем доступность хоста
            $logContext['step'] = 'network_check';
            Log::info('Проверка сетевого подключения:', $logContext);
            
            $connection = @fsockopen($irbisConfig['host'], $irbisConfig['port'], $errno, $errstr, 5);
            if (!$connection) {
                Log::error("Не удается подключиться к {$irbisConfig['host']}:{$irbisConfig['port']} - $errno: $errstr", $logContext);
                throw new \Exception("Не удается подключиться к серверу IRBIS ({$irbisConfig['host']}:{$irbisConfig['port']}): $errstr");
            } else {
                Log::info("Сетевое подключение к {$irbisConfig['host']}:{$irbisConfig['port']} успешно", $logContext);
                fclose($connection);
            }
            
            // 7. Инициализация объекта IRBIS
            $logContext['step'] = 'irbis_init';
            Log::info('Инициализация объекта IRBIS:', $logContext);
            
            try {
                $irbis = new \irbis64(
                    $irbisConfig['host'],
                    $irbisConfig['port'],
                    $irbisConfig['username'],
                    $irbisConfig['password'],
                    $irbisConfig['database']
                );
                Log::info("Объект irbis64 создан успешно", $logContext);
            } catch (\Exception $e) {
                Log::error("Ошибка создания объекта irbis64: " . $e->getMessage(), $logContext);
                throw new \Exception("Ошибка создания объекта irbis64: " . $e->getMessage());
            }
            
            // 8. Попытка входа в систему
            $logContext['step'] = 'irbis_login';
            Log::info('Попытка входа в ИРБИС:', $logContext);
            
            $login_result = $irbis->login();
            Log::info("Результат входа в ИРБИС: " . ($login_result ? 'УСПЕШНО' : 'НЕУДАЧНО'), $logContext);
            
            if (!$login_result) {
                $error_msg = $irbis->error();
                Log::error("Ошибка входа в ИРБИС: " . $error_msg, $logContext);
                throw new \Exception("Ошибка подключения к ИРБИС: " . $error_msg);
            }
            
            // 9. Получение максимального MFN
            $logContext['step'] = 'irbis_mfn_max';
            Log::info('Получение максимального MFN:', $logContext);
            
            $max_mfn = $irbis->mfn_max();
            Log::info("Максимальный MFN: " . $max_mfn, $logContext);
            
            // 10. Закрытие соединения
            $logContext['step'] = 'irbis_logout';
            Log::info('Закрытие соединения ИРБИС:', $logContext);
            
            $irbis->logout();
            Log::info("Соединение с ИРБИС закрыто", $logContext);
            
            // 11. Успешный результат
            $logContext['step'] = 'success';
            Log::info('=== ТЕСТИРОВАНИЕ ЗАВЕРШЕНО УСПЕШНО ===', $logContext);
            
            return response()->json([
                'success' => true,
                'irbis_connected' => true,
                'irbis_max_mfn' => $max_mfn,
                'mysql_connected' => true,
                'organizations_count' => $orgs_count,
                'irbis_config' => [
                    'IRBIS_HOST' => $irbisConfig['host'],
                    'IRBIS_PORT' => $irbisConfig['port'],
                    'IRBIS_USERNAME' => $irbisConfig['username'],
                    'IRBIS_DATABASE' => $irbisConfig['database']
                ]
            ]);
            
        } catch (\Exception $e) {
            $logContext['step'] = 'exception';
            Log::error('=== ОШИБКА ТЕСТИРОВАНИЯ (Exception) ===', array_merge($logContext, [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]));
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => 'Exception',
                'step' => $logContext['step']
            ], 500);
            
        } catch (\Error $e) {
            $logContext['step'] = 'error';
            Log::error('=== ФАТАЛЬНАЯ ОШИБКА ТЕСТИРОВАНИЯ (Error) ===', array_merge($logContext, [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]));
            
            return response()->json([
                'success' => false,
                'error' => 'Фатальная ошибка: ' . $e->getMessage(),
                'error_type' => 'Error',
                'step' => $logContext['step']
            ], 500);
            
        } catch (\Throwable $e) {
            $logContext['step'] = 'throwable';
            Log::error('=== НЕПРЕДВИДЕННАЯ ОШИБКА ТЕСТИРОВАНИЯ (Throwable) ===', array_merge($logContext, [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]));
            
            return response()->json([
                'success' => false,
                'error' => 'Непредвиденная ошибка: ' . $e->getMessage(),
                'error_type' => 'Throwable',
                'step' => $logContext['step']
            ], 500);
        }
    }
}