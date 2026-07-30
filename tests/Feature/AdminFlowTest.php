<?php

namespace Tests\Feature;

use App\Livewire\Admin\TaskShow;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Forms\UserForm;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_select_updates_the_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['status' => 'pending']);

        Livewire::actingAs($user)
            ->test(TaskShow::class, ['task' => $task])
            ->set('form.status', 'in_progress')
            ->assertHasNoErrors();

        $this->assertSame('in_progress', $task->fresh()->status->value);
    }

    public function test_selecting_completed_opens_confirm_modal(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['status' => 'pending']);

        Livewire::actingAs($user)
            ->test(TaskShow::class, ['task' => $task])
            ->call('markCompleted')
            ->assertSet('confirmOpen', true);

        $this->assertSame('pending', $task->fresh()->status->value);
    }

    public function test_creates_a_user_via_crud(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->call('createUser')
            ->set('form.name', 'Jane Support')
            ->set('form.email', 'jane@test.com')
            ->set('form.password', 'secret123')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'jane@test.com', 'name' => 'Jane Support']);
        $this->assertTrue(password_verify('secret123', User::where('email', 'jane@test.com')->value('password')));
    }

    public function test_edits_a_user_without_changing_password(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create(['name' => 'Old Name']);

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->call('editUser', $target->id)
            ->set('form.name', 'New Name')
            ->call('save')
            ->assertHasNoErrors();

        $target->refresh();
        $this->assertSame('New Name', $target->name);
    }

    public function test_cannot_delete_self(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->call('confirmDelete', $admin->id)
            ->call('destroy');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_register_creates_and_logs_in_user(): void
    {
        Livewire::test(\App\Livewire\Auth\Register::class)
            ->set('name', 'New Member')
            ->set('email', 'new@test.com')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.tasks.index'));

        $this->assertDatabaseHas('users', ['email' => 'new@test.com']);
    }
}
