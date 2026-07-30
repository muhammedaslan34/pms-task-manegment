<?php

namespace App\Livewire\Forms;

use App\Enums\TaskStatus;
use App\Models\Task;
use Livewire\Form;

class TaskStatusForm extends Form
{
    public string $status = '';

    public string $resolution_note = '';

    public bool $assign_to_me = false;

    public function setTask(Task $task): void
    {
        $this->status = $task->status->value;
        $this->resolution_note = $task->resolution_note ?? '';
        $this->assign_to_me = $task->assigned_to === auth()->id();
    }

    public function save(Task $task, int $userId): void
    {
        $this->validate([
            'status' => ['required', 'in:' . implode(',', array_column(TaskStatus::cases(), 'value'))],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $isCompleted = $this->status === TaskStatus::Completed->value;

        $task->update([
            'status' => $this->status,
            'resolution_note' => $this->resolution_note ?: null,
            'assigned_to' => $this->assign_to_me ? $userId : $task->assigned_to,
            'completed_at' => $isCompleted ? ($task->completed_at ?? now()) : null,
        ]);
    }
}
