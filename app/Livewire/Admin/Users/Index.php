<?php

namespace App\Livewire\Admin\Users;

use App\Livewire\Forms\UserForm;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    public bool $formModalOpen = false;

    public bool $deleteModalOpen = false;

    public ?User $deleting = null;

    public UserForm $form;

    public array $selected = [];
    public bool $selectAllPage = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedSelectAllPage($value): void
    {
        $ids = $this->userQuery()->forPage($this->getPage(), 10)->pluck('id');

        foreach ($ids as $id) {
            $this->selected[(string) $id] = (bool) $value;
        }
    }

    #[Computed]
    public function selectedIds(): array
    {
        return collect($this->selected)
            ->filter()
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === auth()->id())
            ->all();
    }

    #[Computed]
    public function selectedCount(): int
    {
        return count(array_filter($this->selected));
    }

    public function deleteSelected(): void
    {
        $ids = $this->selectedIds;

        if (empty($ids)) {
            $this->clearSelection();
            return;
        }

        User::whereIn('id', $ids)->delete();
        $count = count($ids);
        $this->clearSelection();
        session()->flash('status', $count . ' user(s) deleted.');
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAllPage = false;
    }

    public function createUser(): void
    {
        $this->form->setCreating();
        $this->formModalOpen = true;
    }

    public function editUser(User $user): void
    {
        $this->form->setEditing($user);
        $this->formModalOpen = true;
    }

    public function save(): void
    {
        if ($this->form->editing) {
            $user = User::findOrFail($this->form->userId);

            if ($user->id === auth()->id() && ! $this->form->is_admin) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'form.is_admin' => __('You cannot remove your own administrator privileges.'),
                ]);
            }

            $this->form->update($user);
            $message = 'User updated.';
        } else {
            $this->form->store();
            $message = 'User created.';
        }

        $this->closeForm();
        session()->flash('status', $message);
    }

    public function closeForm(): void
    {
        $this->formModalOpen = false;
        $this->form->reset();
    }

    public function confirmDelete(User $user): void
    {
        $this->deleting = $user;
        $this->deleteModalOpen = true;
    }

    public function closeDelete(): void
    {
        $this->deleteModalOpen = false;
        $this->deleting = null;
    }

    public function destroy(): void
    {
        if ($this->deleting && $this->deleting->id !== auth()->id()) {
            $this->deleting->delete();
            session()->flash('status', 'User deleted.');
        }

        $this->closeDelete();
    }

    #[Computed]
    public function isSelfDelete(): bool
    {
        return $this->deleting?->id === auth()->id();
    }

    public function render()
    {
        return view('livewire.admin.users.index', [
            'users' => $this->userQuery()->paginate(10),
        ])
            ->layout('components.layouts.app')
            ->title('Users');
    }

    protected function userQuery()
    {
        return User::query()
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('id');
    }
}
