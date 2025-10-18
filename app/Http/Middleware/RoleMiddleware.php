<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // normalize both sides
        $userRole = trim((string) Auth::user()->role);
        $userRole = ucfirst(strtolower($userRole)); // 'admin' -> 'Admin'

        $allowed = array_map(function ($r) {
            $r = trim((string) $r);
            return ucfirst(strtolower($r));
        }, $roles);

        if (!in_array($userRole, $allowed, true)) {
            // Optional: redirect instead of hard 403
            // return redirect()->route('dashboard')->with('error', 'You are not authorized to view that page.');
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
