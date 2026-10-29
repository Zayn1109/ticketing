<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Organizer;
use App\Models\TicketType;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Organizer $organizer): Response
    {
        $events = $organizer->events()
            ->withCount('sessions')
            ->latest()
            ->get();

        return Inertia::render('organizer/events/Index', [
            'organizer' => $organizer->only(['id', 'name']),
            'events' => $events->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'status' => $event->status,
                'sessions_count' => $event->sessions_count,
            ]),
        ]);
    }

    public function show(Organizer $organizer, Event $event): Response
    {
        $event->load([
            'sessions' => fn ($query) => $query->orderBy('starts_at')->with('ticketTypes'),
        ]);

        return Inertia::render('organizer/events/Show', [
            'organizer' => $organizer->only(['id', 'name']),
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'status' => $event->status,
                'published_at' => $event->published_at?->toIso8601String(),
                'sessions' => $event->sessions->map(fn (EventSession $session) => [
                    'id' => $session->id,
                    'name' => $session->name,
                    'venue_name' => $session->venue_name,
                    'venue_address' => $session->venue_address,
                    'starts_at' => $session->starts_at->toIso8601String(),
                    'ends_at' => $session->ends_at->toIso8601String(),
                    'timezone' => $session->timezone,
                    'status' => $session->status,
                    'ticket_types' => $session->ticketTypes->map(fn (TicketType $type) => [
                        'id' => $type->id,
                        'name' => $type->name,
                        'price' => $type->price,
                        'quota' => $type->quota,
                        'sale_start_at' => $type->sale_start_at->toIso8601String(),
                        'sale_end_at' => $type->sale_end_at->toIso8601String(),
                    ]),
                ]),
            ],
        ]);
    }
}
