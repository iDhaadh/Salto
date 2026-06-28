<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanAccessDoorEvents
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canAccessDoorEvents()) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
