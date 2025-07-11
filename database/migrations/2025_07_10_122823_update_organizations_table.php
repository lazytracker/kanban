<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Проверяем, что таблица organizations существует
        if (!Schema::hasTable('organizations')) {
            throw new \Exception('Table organizations does not exist. Please run create_organizations_table migration first.');
        }

        // Приводим id к совместимому типу только если таблица существует
        DB::statement('ALTER TABLE organizations MODIFY id BIGINT UNSIGNED NOT NULL');

        // Добавляем поля с увеличенной длиной (например, 512 символов)
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('shortname1', 512)->nullable()->after('name');
            $table->string('shortname2', 512)->nullable()->after('shortname1');
        });

        // Создаём внешний ключ для таблицы tasks, если таблица существует
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                // Проверяем, что внешний ключ еще не существует
                $foreignKeys = $this->getForeignKeys('tasks');
                $hasOrgForeignKey = collect($foreignKeys)->contains(function ($fk) {
                    return $fk->COLUMN_NAME === 'organization_id';
                });
                
                if (!$hasOrgForeignKey) {
                    $table->foreign('organization_id', 'tasks_organization_id_foreign')
                          ->references('id')
                          ->on('organizations')
                          ->onDelete('cascade');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['shortname1', 'shortname2']);
        });

        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropForeign('tasks_organization_id_foreign');
            });
        }

        DB::statement('ALTER TABLE organizations MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }
    
    /**
     * Получает список внешних ключей для таблицы
     */
    private function getForeignKeys(string $table): array
    {
        $database = config('database.connections.mysql.database');
        return DB::select("
            SELECT COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ? 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database, $table]);
    }
};