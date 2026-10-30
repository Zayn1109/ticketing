<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\EventSessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreEventSessionRequest;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EventSessionController extends Controller
{
    public function create(Organizer $organizer, Event $event): Response
    {
        abort_unless($event->isEditable(), 403);

        return Inertia::render('organizer/events/sessions/Create', [
            'organizer' => $organizer->only(['id', 'name']),
            'event' => $event->only(['id', 'name']),
        ]);
    }

    public function store(StoreEventSessionRequest $request, Organizer $organizer, Event $event): RedirectResponse
    {
        abort_unless($event->isEditable(), 403);

        DB::transaction(function () use ($request, $event): void {
            $session = $event->sessions()->make($request->sessionAttributes());
            $session->status = EventSessionStatus::Scheduled;
            $session->save();

            $event->syncLastSessionEndsAt();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => '場次已新增']);

        return to_route('organizers.events.show', [$organizer, $event]);
    }
}
