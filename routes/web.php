<?php

use App\Livewire\Admin\TaskList;
use App\Livewire\Admin\TaskShow;
use App\Livewire\Auth\Login;
use App\Livewire\Tasks\Create;
use Illuminate\Support\Facades\Route;

Route::get('/', Create::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('home');
})->name('logout')->middleware('auth');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/tasks', TaskList::class)->name('tasks.index');
    Route::get('/tasks/{task}', TaskShow::class)->name('tasks.show');
});
