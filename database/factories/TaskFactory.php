<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        $statuses = TaskStatus::cases();
        $status = fake()->randomElement($statuses);

        return [
            'title' => fake()->randomElement([
                'Login button not working',
                'Cart total shows wrong amount',
                'Images broken on product page',
                'Checkout throws 500 error',
                'Mobile layout broken on dashboard',
                'Date picker overlaps footer',
                'Search returns no results',
                'Profile photo upload fails',
            ]),
            'page_link' => fake()->randomElement([
                'https://example.com/login',
                'https://example.com/cart',
                'https://example.com/products/42',
                'https://app.example.com/dashboard',
                'https://example.com/checkout',
            ]),
            'description' => fake()->paragraph(),
            'screenshot_path' => null,
            'priority' => fake()->randomElement(Priority::cases()),
            'status' => $status,
            'submitted_by' => fake()->optional(0.7)->email(),
            'assigned_to' => $status !== TaskStatus::Pending ? User::factory() : null,
            'resolution_note' => $status === TaskStatus::Completed ? fake()->sentence() : null,
            'completed_at' => $status === TaskStatus::Completed ? fake()->dateTimeBetween('-7 days') : null,
            'created_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }
}
