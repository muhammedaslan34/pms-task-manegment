<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after a task and all of its screenshots have been saved.
 */
class TaskSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Task $task)
    {
    }
}
