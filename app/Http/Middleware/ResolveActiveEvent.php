<?php

namespace App\Http\Middleware;

use App\Models\Event;
use App\Support\ActiveEventContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveActiveEvent
{
    /**
     * Sets the ActiveEventContext from the `{event}` route parameter before any
     * authorization middleware runs, so Gates evaluate against the event in the
     * URL instead of a stale session event.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeEvent = $request->route('event');

        if ($routeEvent !== null) {
            $event = $routeEvent instanceof Event
                ? $routeEvent
                : Event::findOrFail($routeEvent);

            abort_unless($event->isActive(), 404);

            app(ActiveEventContext::class)->set($event);
        }

        return $next($request);
    }
}
