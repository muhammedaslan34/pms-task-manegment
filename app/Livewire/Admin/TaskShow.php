<?php

namespace App\Livewire\Admin;

use App\Enums\TaskStatus;
use App\Livewire\Forms\TaskStatusForm;
use App\Models\Task;
use Livewire\Attributes\Layout;
use Livewire\Component;

class TaskShow extends Component
{
    public Task $task;
    public TaskStatusForm $form;

    public bool $confirmOpen = false;

    public function mount(Task $task): void
    {
        $this->task = $task;
        $this->form->setTask($task);
    }

    public function updatedFormStatus(string $value): void
    {
        if ($value === TaskStatus::Completed->value && empty($this->form->resolution_note)) {
            $this->confirmOpen = true;
            return;
        }

        $this->save();
    }

    public function setInProgress(): void
    {
        $this->form->status = TaskStatus::InProgress->value;
        $this->save();
    }

    public function markCompleted(): void
    {
        $this->form->status = TaskStatus::Completed->value;
        $this->confirmOpen = true;
    }

    public function reopen(): void
    {
        $this->form->status = TaskStatus::Pending->value;
        $this->save();
    }

    public function closeConfirm(): void
    {
        $this->confirmOpen = false;
        $this->form->status = $this->task->status->value;
    }

    public function save(): void
    {
        $this->form->save($this->task);
        $this->task->refresh();
        $this->form->setTask($this->task);
        $this->confirmOpen = false;
        session()->flash('status', 'Task updated.');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.admin.task-show')->title('#' . $this->task->id . ' · ' . $this->task->title);
    }
}
