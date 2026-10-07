<?php

namespace App\Http\Controllers;

use App\Models\TaskImage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskImageController extends Controller
{
    /**
     * Stream a task screenshot from the screenshots disk (e.g. a private S3/iDrive E2 bucket).
     */
    public function __invoke(TaskImage $taskImage): StreamedResponse
    {
        $disk = TaskImage::disk();

        try {
            $stream = $disk->readStream($taskImage->path);
        } catch (\Throwable) {
            $stream = null;
        }

        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $taskImage->mimeType(),
            'Cache-Control' => 'public, max-age=31536000',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
