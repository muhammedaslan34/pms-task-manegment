<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });

        // Migrate any legacy single screenshot_path rows into task_images.
        if (Schema::hasColumn('tasks', 'screenshot_path')) {
            DB::table('tasks')
                ->whereNotNull('screenshot_path')
                ->orderBy('id')
                ->chunkById(200, function ($tasks) {
                    $rows = [];
                    foreach ($tasks as $task) {
                        if ($task->screenshot_path) {
                            $rows[] = [
                                'task_id' => $task->id,
                                'path' => $task->screenshot_path,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                    if ($rows) {
                        DB::table('task_images')->insert($rows);
                    }
                });

            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('screenshot_path');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('screenshot_path')->nullable()->after('description');
        });

        // Move the first image per task back to screenshot_path.
        foreach (DB::table('task_images')->orderBy('id')->get() as $image) {
            DB::table('tasks')->where('id', $image->task_id)->whereNull('screenshot_path')->update([
                'screenshot_path' => $image->path,
            ]);
        }

        Schema::dropIfExists('task_images');
    }
};
