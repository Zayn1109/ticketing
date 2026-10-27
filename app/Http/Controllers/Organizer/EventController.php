<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function index(Organizer $organizer): JsonResponse
    {
        return response()->json(
            $organizer->events()->get(['id', 'name', 'status'])
        );
    }

    public function show(Organizer $organizer, Event $event): JsonResponse
    {
        return response()->json(
            $event->only(['id', 'organizer_id', 'name', 'status'])
        );
    }
}
