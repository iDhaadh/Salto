<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanAccessDoors
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canAccessDoors()) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
