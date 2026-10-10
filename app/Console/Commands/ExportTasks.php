<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskImage;
use Illuminate\Console\Command;

class ExportTasks extends Command
{
    protected $signature = 'tasks:export
        {ids* : Task ids or ranges, e.g. 121 122 124-152}
        {--hours=6 : How long the screenshot URLs stay valid}';

    protected $description = 'Print tasks as JSON lines with downloadable screenshot URLs (for create_task on another instance)';

    public function handle(): int
    {
        $ids = collect($this->argument('ids'))
            ->flatMap(fn (string $id) => preg_match('/^(\d+)-(\d+)$/', $id, $m) ? range((int) $m[1], (int) $m[2]) : [(int) $id])
            ->filter()
            ->unique()
            ->all();

        $expires = now()->addHours(max(1, (int) $this->option('hours')));

        $tasks = Task::with('images')->whereIn('id', $ids)->orderBy('id')->get();

        foreach ($tasks as $task) {
            $this->line(json_encode([
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'page_link' => $task->page_link,
                'priority' => $task->priority?->value,
                'status' => $task->status?->value,
                'submitted_by' => $task->submitted_by,
                'image_urls' => $task->images->map(fn (TaskImage $image) => $this->downloadUrl($image, $expires))->all(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        }

        if ($missing = array_diff($ids, $tasks->modelKeys())) {
            $this->warn('Not found: '.implode(', ', $missing));
        }

        return self::SUCCESS;
    }

    /** A pre-signed bucket URL when the disk supports it, otherwise the app's own screenshot URL. */
    private function downloadUrl(TaskImage $image, \DateTimeInterface $expires): string
    {
        try {
            return TaskImage::disk()->temporaryUrl($image->path, $expires);
        } catch (\Throwable) {
            return $image->imageUrl();
        }
    }
}
