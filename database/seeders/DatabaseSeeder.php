<?php

namespace Database\Seeders;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Delete existing seed data first so re-running `db:seed` resets cleanly.
        $this->truncate();

        $admin = User::factory()->create([
            'name' => 'Support Admin',
            'email' => 'admin@taskflow.test',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'Jane Support',
            'email' => 'jane@taskflow.test',
            'password' => bcrypt('password'),
        ]);

        // A couple of representative, hand-written tasks
        Task::create([
            'title' => 'Checkout button unresponsive on mobile Safari',
            'page_link' => 'https://shop.example.com/checkout',
            'description' => "When tapping the \"Place Order\" button on iPhone (Safari), nothing happens. Console shows a JS error from the payment widget.\n\nSteps:\n1. Add an item to cart\n2. Go to checkout\n3. Fill card details\n4. Tap Place Order -> nothing",
            'priority' => Priority::High,
            'status' => TaskStatus::Pending,
            'submitted_by' => 'customer@example.com',
        ]);

        Task::create([
            'title' => 'Dashboard charts overlap on narrow screens',
            'page_link' => 'https://app.example.com/dashboard',
            'description' => 'The revenue and traffic charts render on top of each other below 768px width.',
            'priority' => Priority::Medium,
            'status' => TaskStatus::InProgress,
            'submitted_by' => 'ops@example.com',
        ]);

        Task::create([
            'title' => 'Footer year shows 2024',
            'page_link' => 'https://example.com',
            'description' => 'The copyright in the footer is hardcoded to 2024. Should be dynamic.',
            'priority' => Priority::Low,
            'status' => TaskStatus::Completed,
            'submitted_by' => 'marketing@example.com',
            'resolution_note' => 'Replaced hardcoded year with Blade date helper across layouts.',
            'completed_at' => now()->subDays(2),
            'created_at' => now()->subDays(4),
        ]);

        // Extra random tasks for a fuller table
        Task::factory()->count(12)->create();
    }

    /**
     * Delete all existing tasks and users before seeding.
     */
    public function truncate(): void
    {
        Task::query()->delete();
        User::query()->delete();

        // Reset auto-increment counters so IDs start fresh.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("DELETE FROM sqlite_sequence WHERE name IN ('tasks', 'users')");
        } else {
            DB::statement('ALTER TABLE tasks AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE users AUTO_INCREMENT = 1');
        }
    }
}
