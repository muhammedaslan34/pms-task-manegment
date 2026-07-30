<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Livewire\Form;

class UserForm extends Form
{
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public bool $editing = false;

    public function rules(): array
    {
        $unique = $this->editing
            ? 'unique:users,email,' . $this->userId
            : 'unique:users,email';

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $unique],
            'password' => array_filter([
                $this->editing ? 'nullable' : 'required',
                'string',
                'min:8',
                'max:255',
            ]),
        ];
    }

    public function setCreating(): void
    {
        $this->reset();
        $this->editing = false;
    }

    public function setEditing(User $user): void
    {
        $this->editing = true;
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
    }

    public function store(): User
    {
        $data = $this->validate();
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];
        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        return User::create($payload);
    }

    public function update(User $user): void
    {
        $data = $this->validate();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ])->save();

        if (filled($data['password'])) {
            $user->password = $data['password'];
            $user->save();
        }
    }
}
