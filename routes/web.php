<?php

use App\Http\Controllers\Organizer\EventController;
use App\Http\Controllers\Organizer\EventSessionController;
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
        Route::get('events/create', [EventController::class, 'create'])
            ->name('events.create')
            ->can('event.manage', 'organizer');
        Route::post('events', [EventController::class, 'store'])
            ->name('events.store')
            ->can('event.manage', 'organizer');
        Route::get('events/{event}', [EventController::class, 'show'])
            ->name('events.show')
            ->can('event.viewAny', 'organizer');
        Route::get('events/{event}/edit', [EventController::class, 'edit'])
            ->name('events.edit')
            ->can('event.manage', 'organizer');
        Route::put('events/{event}', [EventController::class, 'update'])
            ->name('events.update')
            ->can('event.manage', 'organizer');
        Route::get('events/{event}/sessions/create', [EventSessionController::class, 'create'])
            ->name('events.sessions.create')
            ->can('event.manage', 'organizer');
        Route::post('events/{event}/sessions', [EventSessionController::class, 'store'])
            ->name('events.sessions.store')
            ->can('event.manage', 'organizer');
    });

require __DIR__.'/settings.php';
