<?php

namespace App\Livewire\Tasks;

use App\Enums\Priority;
use App\Events\TaskSubmitted;
use App\Models\Task;
use App\Models\TaskImage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        try {
            $paths = $this->storeScreenshots($validated['screenshots'] ?? []);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('screenshots.upload', __('We could not upload your screenshots. Please try again.'));

            return;
        }

        try {
            $task = DB::transaction(function () use ($validated, $paths) {
                $task = Task::create([
                    'title' => $validated['title'],
                    'page_link' => $validated['page_link'] ?: null,
                    'description' => $validated['description'] ?: null,
                    'submitted_by' => $validated['submitted_by'] ?: null,
                    'priority' => $validated['priority'],
                ]);

                foreach ($paths as $path) {
                    $task->images()->create(['path' => $path]);
                }

                return $task;
            });
        } catch (\Throwable $exception) {
            $this->deleteStoredScreenshots($paths);

            throw $exception;
        }

        // The screenshots now live only on the screenshots disk; drop Livewire's temporary copies.
        foreach ($validated['screenshots'] ?? [] as $file) {
            rescue(fn () => $file->delete(), report: false);
        }

        TaskSubmitted::dispatch($task);

        $this->reset(['title', 'page_link', 'description', 'submitted_by', 'priority', 'screenshots']);
        $this->priority = 'medium';
        if (Auth::check()) {
            $this->submitted_by = Auth::user()->email;
        }

        $this->dispatch('task-submitted');
        session()->flash('status', 'Your task has been submitted. Our support team will review it shortly.');
    }

    /**
     * Upload each screenshot straight to the configured screenshots disk (no local copy).
     * If any upload fails, the ones already uploaded are removed and the error is rethrown.
     *
     * @param  array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile>  $files
     * @return list<string>
     */
    private function storeScreenshots(array $files): array
    {
        $disk = config('filesystems.screenshots_disk');
        $paths = [];

        try {
            foreach ($files as $file) {
                $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'png');
                $path = $file->storeAs(
                    config('filesystems.screenshots_directory'),
                    Str::uuid()->toString().'.'.$extension,
                    $disk,
                );

                if (! is_string($path) || $path === '') {
                    throw new \RuntimeException("Failed to store screenshot on [{$disk}] disk.");
                }

                $paths[] = $path;
            }
        } catch (\Throwable $exception) {
            $this->deleteStoredScreenshots($paths);

            throw $exception;
        }

        return $paths;
    }

    /** @param  list<string>  $paths */
    private function deleteStoredScreenshots(array $paths): void
    {
        if ($paths === []) {
            return;
        }

        try {
            TaskImage::disk()->delete($paths);
        } catch (\Throwable $exception) {
            report($exception);
        }
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
