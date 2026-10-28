<?php

use App\Http\Controllers\Organizer\EventController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'organizer.member'])
    ->prefix('organizers/{organizer}')
    ->name('organizers.')
    ->scopeBindings()
    ->group(function () {
        Route::get('events', [EventController::class, 'index'])
            ->name('events.index')
            ->can('event.viewAny', 'organizer');
        Route::get('events/{event}', [EventController::class, 'show'])
            ->name('events.show')
            ->can('event.viewAny', 'organizer');
    });

require __DIR__.'/settings.php';
