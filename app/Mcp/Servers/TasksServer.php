<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\ImplementTask;
use App\Mcp\Tools\CompleteTask;
use App\Mcp\Tools\CreateTask;
use App\Mcp\Tools\GetTask;
use App\Mcp\Tools\ListTasks;
use App\Mcp\Tools\StartTask;
use App\Mcp\Tools\UpdateTaskStatus;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Task Management')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
Access to the bug reports / feature requests that users submitted through the task-management app.
Each task has a title, description, optional page link, priority (high/medium/low), status
(pending -> in_progress -> completed) and optional screenshots.

Workflow for implementing a task:
1. list_tasks to see open work (pending + in_progress, highest priority first, then oldest).
2. get_task with the id to read the full description and look at the screenshots.
3. start_task before you begin, so others can see it is being worked on.
4. Implement the change in the codebase.
5. complete_task with a resolution_note describing what you changed (files, approach, how to verify).
Only complete a task once the work is actually done. If you cannot finish it, use update_task_status
to move it back to pending and explain why in the note.

Use create_task to file a new task (e.g. copied from another TaskFlow instance), with its screenshots as https URLs.
TEXT)]
class TasksServer extends Server
{
    protected array $tools = [
        ListTasks::class,
        GetTask::class,
        StartTask::class,
        CompleteTask::class,
        UpdateTaskStatus::class,
        CreateTask::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        ImplementTask::class,
    ];
}
