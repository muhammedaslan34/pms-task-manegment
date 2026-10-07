<?php

namespace App\Mcp\Tools\Concerns;

use App\Enums\TaskStatus;
use App\Models\Task;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

trait InteractsWithTasks
{
    /** Validate the `id` argument and return the task, or null when it does not exist. */
    protected function findTask(Request $request): ?Task
    {
        $request->validate(['id' => ['required', 'integer', 'min:1']]);

        return Task::find((int) $request->get('id'));
    }

    protected function taskNotFound(Request $request): Response
    {
        return Response::error("Task #{$request->get('id')} not found. Use list_tasks to find valid ids.");
    }

    /** Change the status through the model's single status entry point and describe the result. */
    protected function transition(Task $task, TaskStatus $status, ?string $note = null): Response
    {
        $previous = $task->status;

        $task->setStatus($status, $note);

        return Response::text($this->toJson([
            'message' => "Task #{$task->id} moved from {$previous->value} to {$status->value}.",
            'task' => $this->summary($task->fresh()),
        ]));
    }

    /** @return array<string, mixed> */
    protected function summary(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'priority' => $task->priority?->value,
            'status' => $task->status?->value,
            'submitted_by' => $task->submitted_by,
            'resolution_note' => $task->resolution_note,
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
            'completed_at' => $task->completed_at?->toIso8601String(),
        ];
    }

    protected function toJson(mixed $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
