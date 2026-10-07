<?php

use App\Http\Controllers\TaskImageController;
use App\Livewire\Admin\TaskList;
use App\Livewire\Admin\TaskShow;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Tasks\Create;
use Illuminate\Support\Facades\Route;

Route::get('/', Create::class)->name('home');

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'ar'], true)) {
        session()->put('locale', $locale);
    }

    return redirect()->back();
})->name('locale.switch');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('home');
})->name('logout')->middleware('auth');

Route::get('/media/task-images/{taskImage}', TaskImageController::class)
    ->middleware('auth')
    ->name('task-images.show');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/tasks', TaskList::class)->name('tasks.index');
    Route::get('/tasks/{task}', TaskShow::class)->name('tasks.show');

    Route::middleware('admin')->group(function () {
        Route::get('/users', UsersIndex::class)->name('users.index');
    });
});

// Telegram bot updates. Stateless: no session/cookies/CSRF (the "web" group is removed and the
// path is also CSRF-exempt in bootstrap/app.php); authenticated by the webhook secret header.
Route::post('/telegram/webhook', \App\Http\Controllers\TelegramWebhookController::class)
    ->withoutMiddleware('web')
    ->name('telegram.webhook');
