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
            // Подключаем IRBIS класс
            require_once base_path('irbis_class.inc');

            $this->irbis = new \irbis64('127.0.0.1', 6666, '1', '1', 'organization');

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
        try {
            // Проверяем существование файла
            $irbis_file = base_path('irbis_class.inc');
            if (!file_exists($irbis_file)) {
                throw new \Exception("Файл irbis_class.inc не найден по пути: " . $irbis_file);
            }
            
            // Проверяем MySQL подключение
            try {
                $orgs_count = DB::table('organizations')->count();
                Log::info("MySQL подключение успешно, организаций: " . $orgs_count);
            } catch (\Exception $e) {
                throw new \Exception("Ошибка подключения к MySQL: " . $e->getMessage());
            }
            
            // Подключаем библиотеку ИРБИС
            require_once $irbis_file;
            
            // Проверяем существование класса
            if (!class_exists('irbis64')) {
                throw new \Exception("Класс irbis64 не найден в файле irbis_class.inc");
            }
            
            // Создаем подключение к ИРБИС
            $irbis = new \irbis64('127.0.0.1', 6666, '1', '1', 'organization');
            
            if (!$irbis->login()) {
                throw new \Exception("Ошибка подключения к ИРБИС: " . $irbis->error());
            }
            
            $max_mfn = $irbis->mfn_max();
            $irbis->logout();
            
            return response()->json([
                'success' => true,
                'irbis_connected' => true,
                'irbis_max_mfn' => $max_mfn,
                'mysql_connected' => true,
                'organizations_count' => $orgs_count
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка тестирования подключения: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        } catch (\Error $e) {
            Log::error('Фатальная ошибка тестирования: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Фатальная ошибка: ' . $e->getMessage()
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Непредвиденная ошибка: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Непредвиденная ошибка: ' . $e->getMessage()
            ], 500);
        }
    }
}