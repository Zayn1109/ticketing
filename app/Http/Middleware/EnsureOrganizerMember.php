<?php

namespace App\Http\Middleware;

use App\Models\Organizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizerMember
{
    /**
     * 非該主辦方成員一律回 404，不透露主辦方是否存在（ADR 0005）
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $organizer = $request->route('organizer');
        $user = $request->user();

        if (! $organizer instanceof Organizer || $user === null || $user->roleIn($organizer) === null) {
            abort(404);
        }

        return $next($request);
    }
}
