<?php

use App\Http\Controllers\Organizer\EventController;
use App\Http\Controllers\Organizer\EventSessionController;
use App\Http\Controllers\Organizer\TicketTypeController;
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
        Route::get('events/{event}/sessions/{session}/edit', [EventSessionController::class, 'edit'])
            ->name('events.sessions.edit')
            ->can('event.manage', 'organizer');
        Route::put('events/{event}/sessions/{session}', [EventSessionController::class, 'update'])
            ->name('events.sessions.update')
            ->can('event.manage', 'organizer');
        Route::delete('events/{event}/sessions/{session}', [EventSessionController::class, 'destroy'])
            ->name('events.sessions.destroy')
            ->can('event.manage', 'organizer');
        Route::get('events/{event}/sessions/{session}/ticket-types/create', [TicketTypeController::class, 'create'])
            ->name('events.sessions.ticket-types.create')
            ->can('event.manage', 'organizer');
        Route::post('events/{event}/sessions/{session}/ticket-types', [TicketTypeController::class, 'store'])
            ->name('events.sessions.ticket-types.store')
            ->can('event.manage', 'organizer');
        Route::get('events/{event}/sessions/{session}/ticket-types/{ticketType}/edit', [TicketTypeController::class, 'edit'])
            ->name('events.sessions.ticket-types.edit')
            ->can('event.manage', 'organizer');
        Route::put('events/{event}/sessions/{session}/ticket-types/{ticketType}', [TicketTypeController::class, 'update'])
            ->name('events.sessions.ticket-types.update')
            ->can('event.manage', 'organizer');
        Route::delete('events/{event}/sessions/{session}/ticket-types/{ticketType}', [TicketTypeController::class, 'destroy'])
            ->name('events.sessions.ticket-types.destroy')
            ->can('event.manage', 'organizer');
    });

require __DIR__.'/settings.php';
