<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Log;

class FallbackController extends Controller
{
    /**
     * Handle any request that didn't match a route.
     *
     * - API / AJAX / JSON: return a 404 JSON payload
     * - Authenticated web users: send to dashboard
     * - Guests: send to login (so they can authenticate)
     * - (Optional) If you prefer a 404 page for web GETs, keep the view branch below
     *
     * @return JsonResponse|RedirectResponse|Renderable
     */
    public function __invoke(Request $request)
    {
        // Light logging for diagnostics (safe: no PII)
        Log::warning('Fallback route hit', [
            'path'   => $request->path(),
            'method' => $request->method(),
            'ajax'   => $request->ajax(),
            'accept' => $request->header('Accept'),
        ]);

        // 1) API / JSON callers get a proper 404 JSON
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'ok'      => false,
                'message' => 'Route not found.',
                'path'    => $request->path(),
            ], 404);
        }

        // 2) Web callers: redirect based on auth state
        if (auth()->check()) {
            // Authenticated: send to dashboard
            return redirect()->route('dashboard');
        }

        // 3) Guests: send to login (preserve intended URL)
        return redirect()->guest(route('login'));
        
        /**
         * If you prefer a dedicated 404 page for web GET requests instead of redirecting,
         * replace the two redirects above with:
         *
         * if ($request->method() === 'GET' && view()->exists('errors.404')) {
         *     return response()->view('errors.404', ['path' => $request->path()], 404);
         * }
         * return redirect()->guest(route('login'));
         */
    }
}
