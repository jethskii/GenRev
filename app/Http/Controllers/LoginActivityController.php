<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LoginActivity;
use Illuminate\Http\Request;

class LoginActivityController extends Controller
{
    /**
     * Show login activity log with simple filters.
     *
     * - status: success | failed
     * - search: email / IP / user name
     */
    public function index(Request $request)
    {
        $status = $request->query('status');           // success / failed (optional)
        $search = trim((string) $request->query('search', ''));

        // Use the real column name from your table: login_at
        $q = LoginActivity::with('user')->orderByDesc('login_at');

        // Map status filter to the "succeeded" boolean column
        if ($status === 'success') {
            $q->where('succeeded', 1);
        } elseif ($status === 'failed') {
            $q->where('succeeded', 0);
        }

        // Search over email, ip, agent and related user name/email
        if ($search !== '') {
            $s = strtolower($search);

            $q->where(function ($w) use ($s) {
                $w->whereRaw('LOWER(email) LIKE ?', ["%{$s}%"])
                  ->orWhereRaw('LOWER(ip_address) LIKE ?', ["%{$s}%"])
                  ->orWhereRaw('LOWER(user_agent) LIKE ?', ["%{$s}%"])
                  ->orWhereHas('user', function ($u) use ($s) {
                      $u->whereRaw('LOWER(email) LIKE ?', ["%{$s}%"])
                        ->orWhereRaw('LOWER(name) LIKE ?', ["%{$s}%"]);
                  });
            });
        }

        $activities = $q->paginate(25)->withQueryString();

        return view('settings.login-log', compact('activities', 'status', 'search'));
    }
}
