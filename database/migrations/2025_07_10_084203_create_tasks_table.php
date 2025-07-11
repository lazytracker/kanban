<?php
// database/migrations/2025_07_10_084201_create_tasks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            // Убираем ->constrained() чтобы избежать дублирования внешнего ключа
            $table->foreignId('organization_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('completion_date');
            $table->integer('priority')->default(1);
            $table->enum('status', ['todo', 'in_progress', 'done'])->default('todo');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};