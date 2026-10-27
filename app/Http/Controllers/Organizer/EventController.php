<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
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
}
