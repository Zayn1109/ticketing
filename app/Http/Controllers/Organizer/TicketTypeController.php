<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreTicketTypeRequest;
use App\Http\Requests\Organizer\UpdateTicketTypeRequest;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Organizer;
use App\Models\TicketType;
use Carbon\CarbonInterface;
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
            'session' => $this->sessionProps($session),
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

    public function edit(Organizer $organizer, Event $event, EventSession $session, TicketType $ticketType): Response
    {
        $session->setRelation('event', $event);
        $ticketType->setRelation('eventSession', $session);

        abort_unless($ticketType->isEditable(), 403);

        return Inertia::render('organizer/events/ticket-types/Edit', [
            'organizer' => $organizer->only(['id', 'name']),
            'event' => $event->only(['id', 'name']),
            'session' => $this->sessionProps($session),
            'ticketType' => [
                'id' => $ticketType->id,
                'name' => $ticketType->name,
                'price' => $ticketType->price,
                'quota' => $ticketType->quota,
                'sale_start_at' => $this->toLocalInput($ticketType->sale_start_at, $session->timezone),
                'sale_end_at' => $this->toLocalInput($ticketType->sale_end_at, $session->timezone),
                'max_per_user' => $ticketType->max_per_user,
            ],
        ]);
    }

    public function update(
        UpdateTicketTypeRequest $request,
        Organizer $organizer,
        Event $event,
        EventSession $session,
        TicketType $ticketType,
    ): RedirectResponse {
        $session->setRelation('event', $event);
        $ticketType->setRelation('eventSession', $session);

        abort_unless($ticketType->isEditable(), 403);

        $ticketType->update($request->ticketTypeAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => '票種已更新']);

        return to_route('organizers.events.show', [$organizer, $event]);
    }

    public function destroy(Organizer $organizer, Event $event, EventSession $session, TicketType $ticketType): RedirectResponse
    {
        $session->setRelation('event', $event);
        $ticketType->setRelation('eventSession', $session);

        abort_unless($ticketType->isDeletable(), 403);

        $ticketType->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => '票種已刪除']);

        return to_route('organizers.events.show', [$organizer, $event]);
    }

    /**
     * 表單頁顯示用的場次資訊
     *
     * @return array<string, mixed>
     */
    private function sessionProps(EventSession $session): array
    {
        return [
            'id' => $session->id,
            'name' => $session->name,
            'venue_name' => $session->venue_name,
            'starts_at' => $session->starts_at->toIso8601String(),
            'ends_at' => $session->ends_at->toIso8601String(),
            'timezone' => $session->timezone,
            'capacity' => $session->capacity,
        ];
    }

    /**
     * 轉成 datetime-local 輸入框要的當地時間（YYYY-MM-DDTHH:mm）
     */
    private function toLocalInput(CarbonInterface $at, string $timezone): string
    {
        return $at->copy()->setTimezone($timezone)->format('Y-m-d\TH:i');
    }
}
