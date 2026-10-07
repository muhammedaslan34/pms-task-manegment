<?php

namespace App\Models;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TaskImage extends Model
{
    protected $fillable = [
        'task_id',
        'path',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** The disk task screenshots are stored on (config filesystems.screenshots_disk, e.g. s3). */
    public static function disk(): Filesystem
    {
        return Storage::disk(config('filesystems.screenshots_disk'));
    }

    /** Raw file bytes from the screenshots disk, or null when missing/unreadable. */
    public function contents(): ?string
    {
        try {
            $content = static::disk()->get($this->path);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }

        return $content === null || $content === '' ? null : $content;
    }

    public function mimeType(): string
    {
        return match (strtolower(pathinfo($this->path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }

    /**
     * Browser URL for the screenshot: a pre-signed, expiring bucket URL when the
     * disk supports it (private E2/S3 bucket), otherwise the auth-only proxy route.
     */
    public function imageUrl(): string
    {
        if (config('filesystems.screenshots_public_url')) {
            return rtrim(config('filesystems.screenshots_public_url'), '/').'/'.$this->path;
        }

        if (config('filesystems.disks.'.config('filesystems.screenshots_disk').'.driver') === 's3') {
            try {
                return static::disk()->temporaryUrl(
                    $this->path,
                    now()->addMinutes((int) config('filesystems.screenshots_url_ttl', 60)),
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return route('task-images.show', $this);
    }
}
