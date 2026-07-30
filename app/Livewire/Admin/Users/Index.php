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

    public function updatingSearch(): void
    {
        $this->resetPage();
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
        $users = User::query()
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.admin.users.index', [
            'users' => $users,
        ])
            ->layout('components.layouts.app')
            ->title('Users');
    }
}
