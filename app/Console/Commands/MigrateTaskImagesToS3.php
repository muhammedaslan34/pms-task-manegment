<?php

namespace App\Console\Commands;

use App\Models\TaskImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateTaskImagesToS3 extends Command
{
    protected $signature = 'task-images:migrate-to-s3
        {--delete-local : Remove local copies after a successful upload}
        {--force : Re-upload even when the object already exists on S3}';

    protected $description = 'Copy task screenshots from the local public disk to S3';

    public function handle(): int
    {
        $local = Storage::disk('public');
        $s3 = Storage::disk('s3');

        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar(TaskImage::count());

        TaskImage::query()->orderBy('id')->each(function (TaskImage $image) use ($local, $s3, $bar, &$migrated, &$skipped, &$failed) {
            try {
                if (! $local->exists($image->path)) {
                    if ($s3->exists($image->path)) {
                        $skipped++;
                    } else {
                        $this->newLine();
                        $this->warn("Missing locally and on S3: {$image->path}");
                        $failed++;
                    }

                    return;
                }

                if (! $this->option('force') && $s3->exists($image->path)) {
                    $skipped++;
                } else {
                    $stream = $local->readStream($image->path);
                    $s3->writeStream($image->path, $stream);

                    if (is_resource($stream)) {
                        fclose($stream);
                    }

                    $migrated++;
                }

                if ($this->option('delete-local') && $s3->exists($image->path)) {
                    $local->delete($image->path);
                }
            } catch (\Throwable $exception) {
                $this->newLine();
                $this->error("Failed {$image->path}: {$exception->getMessage()}");
                $failed++;
            } finally {
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Migrated: {$migrated}, Already on S3: {$skipped}, Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
