<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreTicketTypeRequest;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Organizer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TicketTypeController extends Controller
{
    public function create(Organizer $organizer, Event $event, EventSession $session): Response
    {
        $session->setRelation('event', $event);

        abort_unless($session->isEditable(), 403);

        return Inertia::render('organizer/events/ticket-types/Create', [
            'organizer' => $organizer->only(['id', 'name']),
            'event' => $event->only(['id', 'name']),
            'session' => [
                'id' => $session->id,
                'name' => $session->name,
                'venue_name' => $session->venue_name,
                'starts_at' => $session->starts_at->toIso8601String(),
                'ends_at' => $session->ends_at->toIso8601String(),
                'timezone' => $session->timezone,
                'capacity' => $session->capacity,
            ],
        ]);
    }

    public function store(
        StoreTicketTypeRequest $request,
        Organizer $organizer,
        Event $event,
        EventSession $session,
    ): RedirectResponse {
        $session->setRelation('event', $event);

        abort_unless($session->isEditable(), 403);

        $session->ticketTypes()->create($request->ticketTypeAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => '票種已新增']);

        return to_route('organizers.events.show', [$organizer, $event]);
    }
}
