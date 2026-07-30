<?php

namespace App\Livewire\Admin;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Livewire\Forms\TaskStatusForm;
use App\Models\Task;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TaskList extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $status = 'all';

    #[Url(history: true)]
    public string $priority = 'all';

    public string $sortBy = 'created_at';
    public string $sortDir = 'desc';

    public bool $manageModalOpen = false;
    public ?Task $managing = null;
    public TaskStatusForm $form;

    public function updating($property): void
    {
        if (in_array($property, ['search', 'status', 'priority'], true)) {
            $this->resetPage();
        }
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
    }

    public function openManage(Task $task): void
    {
        $this->managing = $task;
        $this->form->setTask($task);
        $this->manageModalOpen = true;
    }

    public function closeManage(): void
    {
        $this->manageModalOpen = false;
        $this->managing = null;
        $this->form->reset();
    }

    public function saveManage(): void
    {
        $this->form->save($this->managing);
        $this->closeManage();
        $this->dispatch('task-updated');
        unset($this->counts);
        session()->flash('status', 'Task updated successfully.');
    }

    public function updateStatus(int $taskId, string $newStatus): void
    {
        $allowed = array_column(TaskStatus::cases(), 'value');

        if (! in_array($newStatus, $allowed, true)) {
            return;
        }

        $task = Task::findOrFail($taskId);

        if ($task->status->value === $newStatus) {
            return;
        }

        $isCompleted = $newStatus === TaskStatus::Completed->value;

        $task->update([
            'status' => $newStatus,
            'completed_at' => $isCompleted ? ($task->completed_at ?? now()) : null,
        ]);

        unset($this->counts);
    }

    #[Computed]
    public function counts()
    {
        return [
            'all' => Task::count(),
            TaskStatus::Pending->value => Task::where('status', TaskStatus::Pending)->count(),
            TaskStatus::InProgress->value => Task::where('status', TaskStatus::InProgress)->count(),
            TaskStatus::Completed->value => Task::where('status', TaskStatus::Completed)->count(),
        ];
    }

    public function render()
    {
        $query = Task::query()
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('page_link', 'like', "%{$this->search}%")
                        ->orWhere('submitted_by', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
            ->when($this->priority !== 'all', fn($q) => $q->where('priority', $this->priority))
            ->orderBy($this->sortBy, $this->sortDir);

        return view('livewire.admin.task-list', [
            'tasks' => $query->paginate(10),
        ])
            ->layout('components.layouts.app')
            ->title('Task Dashboard');
    }
}
