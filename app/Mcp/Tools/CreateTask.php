<?php

namespace App\Mcp\Tools;

use App\Enums\Priority;
use App\Events\TaskSubmitted;
use App\Mcp\Tools\Concerns\InteractsWithTasks;
use App\Models\Task;
use App\Models\TaskImage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[Name('create_task')]
#[IsOpenWorld]
#[Description(<<<'TEXT'
Create a new pending task (bug report / feature request), e.g. to copy a task over from another TaskFlow
instance. Screenshots are given as public or pre-signed https URLs (image_urls): each one is downloaded and
stored with the task, so the URLs only need to be valid while this call runs. Set notify=false when creating
many tasks at once to skip the Telegram "new task" message.
TEXT)]
class CreateTask extends Tool
{
    use InteractsWithTasks;

    public const MAX_IMAGES = 10;

    public const MAX_IMAGE_BYTES = 10 * 1024 * 1024;

    private const IMAGE_EXTENSIONS = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'page_link' => ['nullable', 'string', 'max:500'],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'submitted_by' => ['nullable', 'email', 'max:255'],
            'image_urls' => ['nullable', 'array', 'max:'.self::MAX_IMAGES],
            'image_urls.*' => ['required', 'string', 'url:https', 'max:4000'],
            'notify' => ['nullable', 'boolean'],
        ]);

        $paths = [];

        try {
            foreach ($validated['image_urls'] ?? [] as $index => $url) {
                $paths[] = $this->storeImage($url, $index + 1);
            }
        } catch (\RuntimeException $exception) {
            $this->deleteStoredImages($paths);

            return Response::error($exception->getMessage().' No task was created.');
        }

        try {
            $task = DB::transaction(function () use ($validated, $paths) {
                $task = Task::create([
                    'title' => $validated['title'],
                    'description' => ($validated['description'] ?? null) ?: null,
                    'page_link' => ($validated['page_link'] ?? null) ?: null,
                    'priority' => $validated['priority'] ?? Priority::Medium->value,
                    'submitted_by' => ($validated['submitted_by'] ?? null) ?: null,
                ]);

                foreach ($paths as $path) {
                    $task->images()->create(['path' => $path]);
                }

                return $task;
            });
        } catch (\Throwable $exception) {
            $this->deleteStoredImages($paths);

            throw $exception;
        }

        if ($validated['notify'] ?? true) {
            TaskSubmitted::dispatch($task);
        }

        return Response::text($this->toJson([
            'message' => "Task #{$task->id} created with ".count($paths).' screenshot(s).',
            'task' => [...$this->summary($task->fresh()), 'image_count' => count($paths)],
        ]));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->min(3)
                ->max(180)
                ->description('Short title of the task.')
                ->required(),
            'description' => $schema->string()
                ->max(5000)
                ->description('What needs to be done or what is wrong.'),
            'page_link' => $schema->string()
                ->max(500)
                ->description('The page where the problem occurs (optional).'),
            'priority' => $schema->string()
                ->enum(Priority::class)
                ->description('Priority (default "medium").'),
            'submitted_by' => $schema->string()
                ->format('email')
                ->max(255)
                ->description('Email of the person who reported it (optional).'),
            'image_urls' => $schema->array()
                ->items($schema->string())
                ->max(self::MAX_IMAGES)
                ->description('Up to '.self::MAX_IMAGES.' https URLs of screenshots (png, jpg, gif or webp, max 10 MB each) to download and attach.'),
            'notify' => $schema->boolean()
                ->description('Send the Telegram "new task" notification (default true).'),
        ];
    }

    /** Download one screenshot and put it on the screenshots disk; returns the stored path. */
    private function storeImage(string $url, int $number): string
    {
        $this->assertPublicHost($url, $number);

        try {
            $response = Http::timeout(30)
                ->withOptions(['allow_redirects' => false])
                ->get($url);
        } catch (\Throwable $exception) {
            throw new \RuntimeException("Screenshot {$number} could not be downloaded: {$exception->getMessage()}.");
        }

        if (! $response->successful()) {
            throw new \RuntimeException("Screenshot {$number} could not be downloaded (HTTP {$response->status()}).");
        }

        $body = $response->body();
        $mime = strtolower(trim(Str::before((string) $response->header('Content-Type'), ';')));

        if (! isset(self::IMAGE_EXTENSIONS[$mime])) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($body) ?: '';
        }

        if (! isset(self::IMAGE_EXTENSIONS[$mime])) {
            throw new \RuntimeException("Screenshot {$number} is not a png, jpg, gif or webp image.");
        }

        if ($body === '' || strlen($body) > self::MAX_IMAGE_BYTES) {
            throw new \RuntimeException("Screenshot {$number} is empty or larger than 10 MB.");
        }

        $path = config('filesystems.screenshots_directory').'/'.Str::uuid()->toString().'.'.self::IMAGE_EXTENSIONS[$mime];

        if (! TaskImage::disk()->put($path, $body)) {
            throw new \RuntimeException("Screenshot {$number} could not be stored.");
        }

        return $path;
    }

    /** Refuse URLs that point into private or reserved networks (this server's own infrastructure). */
    private function assertPublicHost(string $url, int $number): void
    {
        $host = trim((string) parse_url($url, PHP_URL_HOST), '[]');
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);

        if ($addresses === []) {
            throw new \RuntimeException("Screenshot {$number}: host \"{$host}\" could not be resolved.");
        }

        foreach ($addresses as $address) {
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new \RuntimeException("Screenshot {$number}: host \"{$host}\" is not a public address.");
            }
        }
    }

    /** @param  list<string>  $paths */
    private function deleteStoredImages(array $paths): void
    {
        if ($paths === []) {
            return;
        }

        try {
            TaskImage::disk()->delete($paths);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
