<?php

namespace App\Mcp\Tools;

use App\Enums\TaskStatus;
use App\Mcp\Tools\Concerns\InteractsWithTasks;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('update_task_status')]
#[Description(<<<'TEXT'
Set a task to any status (pending, in_progress, completed) with an optional note stored as the task's
resolution_note. Prefer start_task / complete_task for the normal flow; use this to move a task back to
pending (blocked, needs clarification: explain why in the note) or to reopen a completed task.
Setting "completed" requires a note.
TEXT)]
class UpdateTaskStatus extends Tool
{
    use InteractsWithTasks;

    public function handle(Request $request): Response
    {
        $task = $this->findTask($request);

        if ($task === null) {
            return $this->taskNotFound($request);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'note' => [
                Rule::requiredIf($request->get('status') === TaskStatus::Completed->value),
                'nullable', 'string', 'max:5000',
            ],
        ], [
            'note.required' => 'A note describing what was done is required when completing a task.',
        ]);

        $note = trim((string) ($validated['note'] ?? ''));

        return $this->transition($task, TaskStatus::from($validated['status']), $note === '' ? null : $note);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->min(1)
                ->description('The task id.')
                ->required(),
            'status' => $schema->string()
                ->enum(TaskStatus::class)
                ->description('The new status.')
                ->required(),
            'note' => $schema->string()
                ->max(5000)
                ->description('Explanation stored as the resolution_note (required for "completed"). Omit to keep the existing note.'),
        ];
    }
}
