<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('key')->unique();
            $table->string('color')->default('#6b7280');
            $table->integer('order')->default(0);
            $table->boolean('is_used')->default(true);
            $table->timestamps();
        });

        DB::table('issue_priorities')->insert([
            ['name' => 'Самый низкий', 'key' => 'lowest', 'color' => '#6b7280', 'order' => 1],
            ['name' => 'Низкий', 'key' => 'low', 'color' => '#9ca3af', 'order' => 2],
            ['name' => 'Средний', 'key' => 'medium', 'color' => '#3b82f6', 'order' => 3],
            ['name' => 'Высокий', 'key' => 'high', 'color' => '#f59e0b', 'order' => 4],
            ['name' => 'Самый высокий', 'key' => 'highest', 'color' => '#ef4444', 'order' => 5],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_priorities');
    }
};
