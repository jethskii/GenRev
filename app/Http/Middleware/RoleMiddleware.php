<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage examples in routes:
     *   RoleMiddleware::class . ':Admin'
     *   RoleMiddleware::class . ':Admin,Sales'
     *   RoleMiddleware::class . ':Admin,Production'            // Production = "production manager"
     *   RoleMiddleware::class . ':masters admin,sales'
     *   RoleMiddleware::class . ':Production|Inventory, Sales' // pipes/commas both supported
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Must be authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Optional: block inactive users (if you have the column)
        if (property_exists($user, 'is_active') && $user->is_active === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'email' => 'Your account is disabled.',
            ]);
        }

        // Normalize current user's role to canonical form
        $userRole = $this->normalizeRole((string) ($user->role ?? ''));

        // Flatten/normalize all allowed roles passed in the middleware params
        $allowedRaw = $this->flattenRoleParams($roles);
        $allowed    = array_values(array_unique(array_map([$this, 'normalizeRole'], $allowedRaw)));

        // If no roles were specified, default-deny
        if (empty($allowed)) {
            abort(403, 'Unauthorized');
        }

        if (!in_array($userRole, $allowed, true)) {
            // Prefer a 403. If you'd rather soft-redirect, use the commented line.
            abort(403, 'Unauthorized');
            // return redirect()->route('dashboard')->with('error', 'You are not authorized to view that page.');
        }

        return $next($request);
    }

    /**
     * Convert arbitrary role strings (from DB or middleware params)
     * into your canonical set:
     *   - "masters admin"
     *   - "production manager"
     *   - "sales"
     *   - "inventory"
     *
     * Accepts many aliases:
     *   Admin, Administrator, Super Admin, Master Admin  => masters admin
     *   Production, Prod Manager, ProductionManager      => production manager
     *   Sales, Inventory                                 => sales, inventory respectively
     */
    private function normalizeRole(string $value): string
    {
        $rawLower = strtolower(trim($value));
        // strip non-letters to tolerate spaces/dashes/etc
        $norm = preg_replace('/[^a-z]/', '', $rawLower);

        // Any admin-ish string => masters admin
        $adminNorms = ['mastersadmin', 'masteradmin', 'admin', 'administrator', 'superadmin', 'superadministrator'];
        if (in_array($norm, $adminNorms, true) || str_contains($norm, 'admin')) {
            return 'masters admin';
        }

        // Production manager aliases
        $prodNorms = ['productionmanager', 'production', 'prodmanager', 'prodman'];
        if (in_array($norm, $prodNorms, true)) {
            return 'production manager';
        }

        // Exact roles
        return match ($norm) {
            'sales'     => 'sales',
            'inventory' => 'inventory',
            default     => $rawLower !== '' ? $rawLower : 'sales', // safe fallback
        };
    }

    /**
     * Flatten middleware role parameters into a simple array of strings.
     * Supports commas and pipes in any argument:
     *   ['Admin,Sales', 'Production|Inventory'] => ['Admin','Sales','Production','Inventory']
     */
    private function flattenRoleParams(array $roles): array
    {
        $out = [];
        foreach ($roles as $r) {
            if (!is_string($r)) continue;
            // split on comma or pipe
            $parts = preg_split('/[,\|]/', $r) ?: [];
            foreach ($parts as $p) {
                $t = trim($p);
                if ($t !== '') $out[] = $t;
            }
        }
        return $out;
    }
}
