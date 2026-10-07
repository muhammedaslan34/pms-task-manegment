<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureAdmin extends Command
{
    protected $signature = 'app:ensure-admin
        {--email= : Admin email (defaults to ADMIN_EMAIL)}
        {--name= : Display name (defaults to ADMIN_NAME or "Admin")}';

    protected $description = 'Create the admin user from ADMIN_EMAIL / ADMIN_PASSWORD if it does not exist yet (never changes an existing user)';

    public function handle(): int
    {
        $email = $this->option('email') ?: config('app.admin.email');
        $password = config('app.admin.password');

        if (! $email) {
            $this->line('ADMIN_EMAIL is not set; skipping.');

            return self::SUCCESS;
        }

        if (User::where('email', $email)->exists()) {
            $this->line("Admin {$email} already exists; leaving it unchanged.");

            return self::SUCCESS;
        }

        if (! $password || strlen($password) < 12) {
            $this->error('ADMIN_PASSWORD must be set (at least 12 characters) to create the admin.');

            return self::FAILURE;
        }

        User::create([
            'name' => $this->option('name') ?: config('app.admin.name'),
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);

        $this->info("Admin {$email} created.");

        return self::SUCCESS;
    }
}
