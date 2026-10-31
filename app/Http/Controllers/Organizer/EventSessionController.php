<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\EventSessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreEventSessionRequest;
use App\Http\Requests\Organizer\UpdateEventSessionRequest;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Organizer;
use Carbon\CarbonInterface;
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

    public function edit(Organizer $organizer, Event $event, EventSession $session): Response
    {
        $session->setRelation('event', $event);

        abort_unless($session->isEditable(), 403);

        return Inertia::render('organizer/events/sessions/Edit', [
            'organizer' => $organizer->only(['id', 'name']),
            'event' => $event->only(['id', 'name']),
            'session' => [
                'id' => $session->id,
                'name' => $session->name,
                'venue_name' => $session->venue_name,
                'venue_address' => $session->venue_address,
                'timezone' => $session->timezone,
                'starts_at' => $this->toLocalInput($session->starts_at, $session->timezone),
                'ends_at' => $this->toLocalInput($session->ends_at, $session->timezone),
                'doors_open_at' => $session->doors_open_at
                    ? $this->toLocalInput($session->doors_open_at, $session->timezone)
                    : null,
                'capacity' => $session->capacity,
            ],
        ]);
    }

    public function update(
        UpdateEventSessionRequest $request,
        Organizer $organizer,
        Event $event,
        EventSession $session,
    ): RedirectResponse {
        $session->setRelation('event', $event);

        abort_unless($session->isEditable(), 403);

        DB::transaction(function () use ($request, $event, $session): void {
            $session->update($request->sessionAttributes());

            $event->syncLastSessionEndsAt();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => '場次已更新']);

        return to_route('organizers.events.show', [$organizer, $event]);
    }

    public function destroy(Organizer $organizer, Event $event, EventSession $session): RedirectResponse
    {
        $session->setRelation('event', $event);

        abort_unless($session->isDeletable(), 403);

        DB::transaction(function () use ($event, $session): void {
            // ticket_types 外鍵為 restrictOnDelete，要先刪；只有草稿能刪，票種不會有訂單
            $session->ticketTypes()->delete();
            $session->delete();

            $event->syncLastSessionEndsAt();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => '場次已刪除']);

        return to_route('organizers.events.show', [$organizer, $event]);
    }

    /**
     * 轉成 datetime-local 輸入框要的當地時間（YYYY-MM-DDTHH:mm）
     */
    private function toLocalInput(CarbonInterface $at, string $timezone): string
    {
        return $at->copy()->setTimezone($timezone)->format('Y-m-d\TH:i');
    }
}
