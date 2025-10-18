<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserManagementController extends Controller
{
    public function __construct()
    {
        // Only Admins can touch user management (UI + actions)
        $this->middleware(function ($request, $next) {
            $u = $request->user();
            if (!$u || strtolower($u->role ?? '') !== 'admin') {
                return redirect()->route('dashboard')
                    ->with('error', 'Access denied. Admins only.');
            }
            return $next($request);
        });
    }

    /**
     * List users (including soft-deleted) and render UI.
     */
    public function index(Request $request)
    {
        $users = User::withTrashed()
            ->orderBy('name')
            ->get();

        return view('settings.user-management', [
            'users'        => $users,
            'openAddModal' => $request->boolean('add'),
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(Request $request)
    {
        // Normalize role (case-insensitive input)
        $request->merge(['role' => strtolower((string) $request->input('role'))]);

        $table = (new User)->getTable();
        $validRoles = defined(User::class.'::KNOWN_ROLES') ? User::KNOWN_ROLES : ['admin','sales','inventory'];

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => [
                'required', 'email', 'max:255',
                Rule::unique($table, 'email')->whereNull('deleted_at'),
            ],
            'password'  => ['required', 'string', 'min:6'],
            'role'      => ['required', Rule::in($validRoles)],   // already lowercased
            'is_active' => ['nullable', 'boolean'],
        ]);

        User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'role'      => $validated['role'],                    // stored lowercase
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    /**
     * Return a single user (for AJAX Edit modal prefill).
     */
    public function edit(User $user)
    {
        return response()->json($user);
    }

    /**
     * Update a user.
     */
    public function update(Request $request, User $user)
    {
        // Normalize role
        $request->merge(['role' => strtolower((string) $request->input('role'))]);

        $table = (new User)->getTable();
        $validRoles = defined(User::class.'::KNOWN_ROLES') ? User::KNOWN_ROLES : ['admin','sales','inventory'];

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => [
                'required', 'email', 'max:255',
                Rule::unique($table, 'email')
                    ->ignore($user->id)
                    ->whereNull('deleted_at'),
            ],
            'password'  => ['nullable', 'string', 'min:6'],
            'role'      => ['required', Rule::in($validRoles)],   // lowercased
            'is_active' => ['nullable', 'boolean'],
        ]);

        $currentRole = strtolower($user->role);
        $newRole     = $validated['role'];

        // Guard: Don't let the last active admin lose admin role
        if ($currentRole === 'admin' && $newRole !== 'admin' && $this->isLastActiveAdmin($user->id)) {
            return back()->with('error', 'Cannot change role. This is the last active Admin.');
        }

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        $user->role  = $newRole;

        // Active flag
        $newActive = $request->boolean('is_active', $user->is_active);
        if ($currentRole === 'admin' && $user->is_active && !$newActive && $this->isLastActiveAdmin($user->id)) {
            return back()->with('error', 'Cannot deactivate the last active Admin.');
        }
        $user->is_active = $newActive;

        // Optional password update
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    /**
     * Soft-delete a user.
     */
    public function destroy(User $user)
    {
        // Guard: no self-delete
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Guard: cannot delete last active admin
        if (strtolower($user->role) === 'admin' && $user->is_active && $this->isLastActiveAdmin($user->id)) {
            return back()->with('error', 'Cannot delete the last active Admin.');
        }

        $user->delete(); // Soft delete

        return redirect()->route('users.index')->with('success', 'User moved to trash.');
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore($userId)
    {
        $user = User::withTrashed()->findOrFail($userId);
        $user->restore();

        return redirect()->route('users.index')->with('success', 'User restored.');
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleActive(User $user)
    {
        $new = !$user->is_active;

        // Guard: cannot deactivate last active admin
        if (strtolower($user->role) === 'admin' && $user->is_active && !$new && $this->isLastActiveAdmin($user->id)) {
            return back()->with('error', 'Cannot deactivate the last active Admin.');
        }

        // Guard: if activating/deactivating a trashed user, block
        if ($user->trashed()) {
            return back()->with('error', 'Cannot change status of a deleted user. Restore first.');
        }

        $user->is_active = $new;
        $user->save();

        return redirect()->route('users.index')->with('success', $new ? 'User activated.' : 'User deactivated.');
    }

    /**
     * Reset a user's password (manual entry with confirmation).
     */
    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password'              => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:6'],
        ]);

        $user->password = Hash::make($data['password']);
        $user->save();

        return redirect()->route('users.index')->with('success', 'Password reset successfully.');
    }

    /**
     * Export users as CSV (includes soft-deleted).
     */
    public function exportCsv(): StreamedResponse
    {
        $rows = User::withTrashed()
            ->orderBy('name')
            ->get(['id','name','email','role','is_active','deleted_at','created_at']);

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users.csv"',
        ];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID','Name','Email','Role','Active','Deleted At','Created At']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id,
                    $r->name,
                    $r->email,
                    $r->role,                                  // lowercase
                    $r->is_active ? '1' : '0',
                    optional($r->deleted_at)->toDateTimeString(),
                    optional($r->created_at)->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, SymfonyResponse::HTTP_OK, $headers);
    }

    /* ---------------------------------
     * Helpers
     * --------------------------------- */

    /**
     * Count active admins; when $excludeId is given, exclude that user.
     */
    private function activeAdminCount(?int $excludeId = null): int
    {
        return User::query()
            ->whereNull('deleted_at')
            ->where('role', 'admin')       // lowercase
            ->where('is_active', true)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->count();
    }

    /**
     * Is the provided user ID the last active Admin?
     */
    private function isLastActiveAdmin(int $userId): bool
    {
        return $this->activeAdminCount($userId) === 0;
    }
}
