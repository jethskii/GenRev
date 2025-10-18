<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when not authenticated.
     * For APIs or AJAX, Laravel will return a 401 JSON automatically.
     */
    protected function redirectTo(Request $request): ?string
    {
        // If the request expects JSON (API, fetch/AJAX), don't redirect
        if ($request->expectsJson()) {
            return null;
        }

        // Otherwise send them to the named login route
        return route('login');
    }
}
