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

    public $screenshot = null;

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
            'screenshot' => ['nullable', 'image', 'max:10240'],
        ];
    }

    public function updatedScreenshot(): void
    {
        $this->validateOnly('screenshot');
    }

    public function removeScreenshot(): void
    {
        $this->screenshot = null;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $path = null;
        if ($this->screenshot) {
            $path = $this->screenshot->store('screenshots', 'public');
        }

        Task::create([
            'title' => $validated['title'],
            'page_link' => $validated['page_link'] ?: null,
            'description' => $validated['description'] ?: null,
            'submitted_by' => $validated['submitted_by'] ?: null,
            'priority' => $validated['priority'],
            'screenshot_path' => $path,
        ]);

        $this->reset(['title', 'page_link', 'description', 'submitted_by', 'priority', 'screenshot']);
        $this->priority = 'medium';
        if (Auth::check()) {
            $this->submitted_by = Auth::user()->email;
        }

        $this->dispatch('task-submitted');
        session()->flash('status', 'Your task has been submitted. Our support team will review it shortly.');
    }

    public function render()
    {
        return view('livewire.tasks.create')
            ->layout('components.layouts.app')
            ->title(__('Submit a Task'));
    }
}
