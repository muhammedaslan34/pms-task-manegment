<?php

namespace App\Mcp\Tools;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Mcp\Tools\Concerns\InteractsWithTasks;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_tasks')]
#[IsReadOnly]
#[Description(<<<'TEXT'
List submitted tasks (bug reports / feature requests). By default returns OPEN tasks (pending + in_progress),
ordered by priority (high first) and then oldest first, so the first result is the most urgent work.
Returns compact JSON: id, title, priority, status, submitted_by, created_at, image_count.
Call get_task with an id to read the full description and see the screenshots.
TEXT)]
class ListTasks extends Tool
{
    use InteractsWithTasks;

    public const DEFAULT_LIMIT = 25;

    public const MAX_LIMIT = 100;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in($this->statusFilters())],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'search' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
        ]);

        $status = $validated['status'] ?? 'open';
        $priority = $validated['priority'] ?? null;
        $search = $validated['search'] ?? null;

        $query = Task::query()
            ->withCount('images')
            ->when($status === 'open', fn (Builder $q) => $q->whereIn('status', [TaskStatus::Pending, TaskStatus::InProgress]))
            ->when(! in_array($status, ['open', 'all'], true), fn (Builder $q) => $q->where('status', $status))
            ->when($priority, fn (Builder $q) => $q->where('priority', $priority))
            ->when($search, function (Builder $q) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $q->where(fn (Builder $q) => $q
                    ->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('page_link', 'like', $like)
                    ->orWhere('submitted_by', 'like', $like));
            });

        $total = (clone $query)->count();

        $tasks = $query
            ->orderByRaw('case priority when ? then 0 when ? then 1 when ? then 2 else 3 end', [
                Priority::High->value, Priority::Medium->value, Priority::Low->value,
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit((int) ($validated['limit'] ?? self::DEFAULT_LIMIT))
            ->get()
            ->map(fn (Task $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'priority' => $task->priority?->value,
                'status' => $task->status?->value,
                'submitted_by' => $task->submitted_by,
                'created_at' => $task->created_at?->toIso8601String(),
                'image_count' => $task->images_count,
            ]);

        return Response::text($this->toJson([
            'filter' => array_filter(['status' => $status, 'priority' => $priority, 'search' => $search]),
            'total' => $total,
            'returned' => $tasks->count(),
            'tasks' => $tasks->all(),
        ]));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum($this->statusFilters())
                ->description('Which tasks to return: "open" (default) = pending + in_progress, "all" = every task, or one exact status.'),
            'priority' => $schema->string()
                ->enum(Priority::class)
                ->description('Only return tasks with this priority.'),
            'search' => $schema->string()
                ->max(255)
                ->description('Text to look for in the title, description, page link or submitter.'),
            'limit' => $schema->integer()
                ->min(1)
                ->max(self::MAX_LIMIT)
                ->description('Maximum number of tasks to return (default '.self::DEFAULT_LIMIT.', max '.self::MAX_LIMIT.').'),
        ];
    }

    /** @return list<string> */
    private function statusFilters(): array
    {
        return ['open', 'all', ...array_column(TaskStatus::cases(), 'value')];
    }
}
