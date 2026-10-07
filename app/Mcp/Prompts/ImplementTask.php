<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('implement_task')]
#[Description('Instructions for picking up a submitted task, implementing it in this codebase and closing it with a resolution note.')]
class ImplementTask extends Prompt
{
    public function handle(Request $request): Response
    {
        $request->validate(['id' => ['nullable', 'integer', 'min:1']]);

        $id = $request->get('id');

        $first = $id
            ? "1. Call get_task with id {$id} and read the description, page link and every screenshot carefully."
            : '1. Call list_tasks (open tasks, most urgent first), pick the first task that is still "pending", then call get_task with its id and read the description, page link and every screenshot carefully.';

        $subject = $id ? "task #{$id}" : 'the most urgent open task';

        return Response::text(implode("\n", [
            "Implement {$subject} from the task-management queue.",
            '',
            $first,
            '2. Call start_task with the id so it shows as in progress.',
            '3. Find the relevant code in this repository and implement the fix or feature. Keep the change focused, follow existing conventions, add or update tests where it makes sense, and run the test suite.',
            '4. When the work is done and verified, call complete_task with a clear resolution_note: what was wrong or requested, what you changed (files/components), and how to verify it.',
            '5. If you cannot finish (unclear requirements, blocked, out of scope), do NOT complete it: call update_task_status with status "pending" and a note explaining what is missing.',
        ]));
    }

    public function arguments(): array
    {
        return [
            new Argument('id', 'The task id to implement. Omit to pick the most urgent open task.', required: false),
        ];
    }
}
