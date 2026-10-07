<?php

namespace App\Mcp\Tools;

use App\Enums\TaskStatus;
use App\Mcp\Tools\Concerns\InteractsWithTasks;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('start_task')]
#[IsIdempotent]
#[Description(<<<'TEXT'
Mark a task as in_progress. Call this right before you start implementing a task so others can see it is
being worked on. Does nothing if the task is already in_progress. Refuses to reopen a completed task
(use update_task_status for that, deliberately).
TEXT)]
class StartTask extends Tool
{
    use InteractsWithTasks;

    public function handle(Request $request): Response
    {
        $task = $this->findTask($request);

        if ($task === null) {
            return $this->taskNotFound($request);
        }

        if ($task->status === TaskStatus::Completed) {
            return Response::error("Task #{$task->id} is already completed. Use update_task_status with status \"in_progress\" if you really need to reopen it.");
        }

        if ($task->status === TaskStatus::InProgress) {
            return Response::text($this->toJson([
                'message' => "Task #{$task->id} is already in_progress.",
                'task' => $this->summary($task),
            ]));
        }

        return $this->transition($task, TaskStatus::InProgress);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->min(1)
                ->description('The id of the task you are starting.')
                ->required(),
        ];
    }
}
