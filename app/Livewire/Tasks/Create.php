<?php

namespace App\Livewire\Tasks;

use App\Enums\Priority;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public string $title = '';
    public string $page_link = '';
    public string $description = '';
    public string $submitted_by = '';
    public string $priority = 'medium';

    public array $screenshots = [];

    public function mount(): void
    {
        if (Auth::check()) {
            $this->submitted_by = Auth::user()->email;
        }
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:180'],
            'page_link' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'submitted_by' => ['nullable', 'email', 'max:255'],
            'priority' => ['required', 'in:' . implode(',', array_column(Priority::cases(), 'value'))],
            'screenshots.*' => ['nullable', 'image', 'max:10240'],
        ];
    }

    public function updatedScreenshots(): void
    {
        $this->validateOnly('screenshots.*');
    }

    public function removeScreenshot(int $index): void
    {
        unset($this->screenshots[$index]);
        $this->screenshots = array_values($this->screenshots);
    }

    public function save(): void
    {
        $validated = $this->validate();

        $task = Task::create([
            'title' => $validated['title'],
            'page_link' => $validated['page_link'] ?: null,
            'description' => $validated['description'] ?: null,
            'submitted_by' => $validated['submitted_by'] ?: null,
            'priority' => $validated['priority'],
        ]);

        foreach ($validated['screenshots'] ?? [] as $file) {
            $task->images()->create([
                'path' => $file->store('screenshots', 'public'),
            ]);
        }

        $this->reset(['title', 'page_link', 'description', 'submitted_by', 'priority', 'screenshots']);
        $this->priority = 'medium';
        if (Auth::check()) {
            $this->submitted_by = Auth::user()->email;
        }

        $this->dispatch('task-submitted');
        session()->flash('status', 'Your task has been submitted. Our support team will review it shortly.');
    }

    public function render()
    {
        return view('livewire.tasks.create', [
            'users' => Auth::guest() ? User::orderBy('name')->get() : collect(),
        ])
            ->layout('components.layouts.app')
            ->title(__('Submit a Task'));
    }
}
