<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Only admins, managers and stylists may use the back office. */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isStaff() || ! $user->active) {
            abort(403, 'The admin area is for the salon team.');
        }

        return $next($request);
    }
}
