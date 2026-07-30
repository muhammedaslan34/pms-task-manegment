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

    protected ?Task $task = null;

    public function setTask(Task $task): void
    {
        $this->task = $task;
        $this->status = $task->status->value;
        $this->resolution_note = $task->resolution_note ?? '';
        $this->assign_to_me = $task->assigned_to === auth()->id();
    }

    public function save(int $userId): void
    {
        $this->validate([
            'status' => ['required', 'in:' . implode(',', array_column(TaskStatus::cases(), 'value'))],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $this->task) {
            return;
        }

        $isCompleted = $this->status === TaskStatus::Completed->value;

        $this->task->update([
            'status' => $this->status,
            'resolution_note' => $this->resolution_note ?: null,
            'assigned_to' => $this->assign_to_me ? $userId : $this->task->assigned_to,
            'completed_at' => $isCompleted ? ($this->task->completed_at ?? now()) : null,
        ]);
    }
}
