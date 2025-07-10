<?php
// database/migrations/xxxx_xx_xx_create_tasks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('description')->nullable();
    $table->timestamp('completion_date');
    $table->integer('priority')->default(1);
    $table->enum('status', ['todo', 'in_progress', 'done'])->default('todo');
    $table->timestamps(); // автоматически добавляет created_at и updated_at
});
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};