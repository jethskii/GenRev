<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // If not logged in, send back to login with a friendly message.
        // (auth middleware should normally run before this, but this is safe.)
        if (!$user) {
            return redirect()
                ->route('login')
                ->with('error', 'Please sign in to continue.');
        }

        // 1) Check main user record "is_active" flag
        if (isset($user->is_active) && $user->is_active === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Your account has been deactivated. Please contact the administrator.';

            return redirect()
                ->route('login')
                ->withErrors(['email' => $message])
                ->with('error', $message);
        }

        // 2) If there is a linked employee, respect the employee status
        if (method_exists($user, 'employee')) {
            $employee = $user->relationLoaded('employee')
                ? $user->employee
                : $user->employee()->first();

            if ($employee && isset($employee->status) && strtolower((string) $employee->status) !== 'active') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $message = 'Your employee account is blocked or inactive. Please contact the administrator.';

                return redirect()
                    ->route('login')
                    ->withErrors(['email' => $message])
                    ->with('error', $message);
            }
        }

        return $next($request);
    }
}
