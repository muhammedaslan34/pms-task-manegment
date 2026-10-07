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

#[Name('complete_task')]
#[Description(<<<'TEXT'
Mark a task as completed once the work is actually done. A resolution_note is required: describe what was
changed (files/components touched, the fix or feature implemented, anything the submitter should verify).
The note is shown to the people who submitted and triage the task.
TEXT)]
class CompleteTask extends Tool
{
    use InteractsWithTasks;

    public function handle(Request $request): Response
    {
        $task = $this->findTask($request);

        if ($task === null) {
            return $this->taskNotFound($request);
        }

        $validated = $request->validate([
            'resolution_note' => ['required', 'string', 'min:10', 'max:5000'],
        ], [
            'resolution_note.required' => 'A resolution_note describing what was done is required.',
            'resolution_note.min' => 'The resolution_note is too short; describe what was changed.',
        ]);

        return $this->transition($task, TaskStatus::Completed, trim($validated['resolution_note']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->min(1)
                ->description('The id of the task you finished.')
                ->required(),
            'resolution_note' => $schema->string()
                ->min(10)
                ->max(5000)
                ->description('What was done to resolve the task: changes made, files touched, how to verify.')
                ->required(),
        ];
    }
}
