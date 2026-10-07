<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\InteractsWithTasks;
use App\Models\TaskImage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_task')]
#[IsReadOnly]
#[Description(<<<'TEXT'
Get the full details of one task by id: title, description, page_link (the page where the problem occurs),
priority, status, submitter, resolution_note and timestamps. The task's screenshots are attached as image
content so you can look at them (up to 5 images of at most ~3.5 MB each); every screenshot is also listed
with a URL in the JSON, including any that could not be embedded.
TEXT)]
class GetTask extends Tool
{
    use InteractsWithTasks;

    public const MAX_IMAGES = 5;

    /** Raw bytes; keeps each base64 payload under the common 5 MB per-image limit of AI clients. */
    public const MAX_IMAGE_BYTES = 3_500_000;

    public function handle(Request $request): Response|ResponseFactory
    {
        $task = $this->findTask($request);

        if ($task === null) {
            return $this->taskNotFound($request);
        }

        $request->validate(['include_images' => ['nullable', 'boolean']]);
        $includeImages = $request->get('include_images') ?? true;

        $images = [];
        $screenshots = [];

        /** @var TaskImage $image */
        foreach ($task->images()->orderBy('id')->get() as $image) {
            $entry = [
                'id' => $image->id,
                'url' => $image->imageUrl(),
                'mime_type' => $image->mimeType(),
                'embedded' => false,
            ];

            if (! $includeImages) {
                $screenshots[] = $entry;

                continue;
            }

            if (count($images) >= self::MAX_IMAGES) {
                $entry['note'] = 'Not embedded (limit of '.self::MAX_IMAGES.' images per response); use the URL.';
            } elseif (($bytes = $image->contents()) === null) {
                $entry['note'] = 'Could not be read from storage; use the URL.';
            } elseif (strlen($bytes) > self::MAX_IMAGE_BYTES) {
                $entry['note'] = 'Too large to embed ('.number_format(strlen($bytes)).' bytes); use the URL.';
            } else {
                $images[] = Response::image($bytes, $image->mimeType());
                $entry['embedded'] = true;
                $entry['attachment_number'] = count($images);
            }

            $screenshots[] = $entry;
        }

        $text = Response::text($this->toJson([
            ...$this->summary($task),
            'page_link' => $task->page_link,
            'description' => $task->description,
            'screenshots' => $screenshots,
        ]));

        if ($images === []) {
            return $text;
        }

        return Response::make([
            $text,
            Response::text(count($images).' screenshot(s) attached below, in the order of "attachment_number".'),
            ...$images,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->min(1)
                ->description('The task id (from list_tasks).')
                ->required(),
            'include_images' => $schema->boolean()
                ->default(true)
                ->description('Attach the screenshots as images (default true). Set false to only get their URLs.'),
        ];
    }
}
