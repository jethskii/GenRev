<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class AuthController extends Controller
{
    /**
     * You can override these via env:
     * AUTH_MAX_ATTEMPTS=5
     * AUTH_DECAY_SECONDS=60
     */
    private const DEFAULT_MAX_ATTEMPTS  = 5;
    private const DEFAULT_DECAY_SECONDS = 60;

    /* =========================
     |  Auth Views (GET)
     * ========================*/

    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    /* =========================
     |  Actions (POST)
     * ========================*/

    /**
     * Login with email OR employee.username (case-insensitive).
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            // May be an email or a username; keep it as string
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $raw        = trim($data['email']);
        $identifier = Str::lower($raw);
        $remember   = (bool) ($data['remember'] ?? false);

        $maxAttempts  = (int) (config('auth.max_attempts', env('AUTH_MAX_ATTEMPTS', self::DEFAULT_MAX_ATTEMPTS)));
        $decaySeconds = (int) (config('auth.decay_seconds', env('AUTH_DECAY_SECONDS', self::DEFAULT_DECAY_SECONDS)));

        // throttle per IP + identifier (+ UA to make abuse a bit harder)
        $throttleKey = $this->throttleKey((string) $request->ip(), $identifier, (string) $request->userAgent());
        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => $this->lockoutMessage($seconds, $maxAttempts)]);
        }

        try {
            $credentials = null;

            if (str_contains($raw, '@')) {
                // Email login (case-insensitive)
                $credentials = [
                    'email'    => Str::lower($raw),
                    'password' => $data['password'],
                ];
            } else {
                // Username login via employees.username -> users.email
                $employee = Employee::query()
                    ->whereRaw('LOWER(username) = ?', [$identifier])
                    ->first();

                // Optional: block disabled employees if you track status
                if ($employee && isset($employee->status) && strtolower((string) $employee->status) === 'disabled') {
                    RateLimiter::hit($throttleKey, $decaySeconds);
                    return back()
                        ->withInput($request->only('email', 'remember'))
                        ->withErrors(['email' => 'This account is disabled.']);
                }

                if ($employee?->user_id) {
                    $user = User::find($employee->user_id);
                    if ($user) {
                        $credentials = [
                            'email'    => Str::lower($user->email),
                            'password' => $data['password'],
                        ];
                    }
                }
            }

            if ($credentials && Auth::attempt($credentials, $remember)) {
                RateLimiter::clear($throttleKey);
                $request->session()->regenerate();

                // Optional: update last_login_at if you have the column
                try {
                    $authUser = Auth::user();
                    if ($authUser && method_exists($authUser, 'forceFill')) {
                        $authUser->forceFill(['last_login_at' => now()])->save();
                    }
                } catch (\Throwable $e) {
                    // ignore if column doesn't exist
                }

                return redirect()->intended(route('dashboard'));
            }

            RateLimiter::hit($throttleKey, $decaySeconds);
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Invalid credentials.']);

        } catch (QueryException $e) {
            // Handle missing users or employees table (42S02) gracefully
            if ($this->isMissingTableError($e)) {
                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors(['email' =>
                        'A required table is missing (users or employees). Run your migrations and clear caches.']);
            }
            throw $e; // bubble up other DB errors
        }
    }

    /**
     * Register a User and link (or create) an Employee.
     * - Accepts either first_name/last_name or a single "name" to split.
     * - Enforces roles: admin | sales | inventory.
     */
    public function register(Request $request)
    {
        // Allow single "name" field; auto-split into first/last
        $fullName = trim((string) $request->input('name', ''));
        $first    = $request->input('first_name');
        $last     = $request->input('last_name');

        if (!$first || !$last) {
            [$firstAuto, $lastAuto] = $this->splitName($fullName);
            $request->merge([
                'first_name' => $first ?: ($firstAuto ?: null),
                'last_name'  => $last  ?: ($lastAuto  ?: null),
            ]);
        }

        // Fallback: use email as username if not provided
        $request->merge([
            'username' => $request->input('username') ?: $request->input('email'),
        ]);

        // Dynamically resolve the actual users table (supports User::getTable())
        $usersTable = $this->usersTable();

        $validated = $request->validate([
            'name'       => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name'  => ['required', 'string', 'max:120'],
            'position'   => ['nullable', 'string', 'max:160'],
            'username'   => ['required', 'string', 'max:120', Rule::unique('employees', 'username')],
            'email'      => ['required', 'email', Rule::unique($usersTable, 'email')],
            'password'   => ['required', 'string', 'min:6', 'confirmed'],
            'role'       => ['required', Rule::in(['admin', 'sales', 'inventory'])],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $email    = Str::lower($validated['email']);
                $username = Str::lower($validated['username']);

                // Create auth user (make sure password is hashed here)
                /** @var \App\Models\User $user */
                $user = User::create([
                    'name'     => trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? '')),
                    'email'    => $email,
                    'password' => Hash::make($validated['password']),
                    'role'     => Str::lower($validated['role']),
                ]);

                // Attach to existing employee by email; otherwise create
                /** @var \App\Models\Employee|null $existing */
                $existing = Employee::whereRaw('LOWER(email) = ?', [$email])->first();

                if ($existing) {
                    $existing->user_id  = $existing->user_id ?: $user->id;
                    $existing->username = $existing->username ?: $username;
                    $existing->status   = $existing->status ?: 'active';
                    $existing->save();
                } else {
                    Employee::create([
                        'user_id'    => $user->id,
                        'first_name' => $validated['first_name'],
                        'last_name'  => $validated['last_name'],
                        'position'   => $validated['position'] ?? null,
                        'email'      => $email,
                        'username'   => $username,
                        'status'     => 'active',
                    ]);
                }
            });

            return redirect()->route('login')->with('success', 'Account created successfully. Please log in.');

        } catch (QueryException $e) {
            if ($this->isMissingTableError($e)) {
                // Friendly guidance when required tables are missing or renamed
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['email' =>
                        "A required table is missing. Ensure your auth and employees tables exist, then run:\n" .
                        "php artisan migrate --force\n" .
                        "php artisan config:clear && php artisan cache:clear && php artisan optimize:clear"]);
            }

            // Handle unique collisions that slip past validation under race conditions
            if ($this->isUniqueConstraintError($e)) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['email' => 'That email or username was just taken. Please try a different one.']);
            }

            throw $e;
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }

    /* =========================
     |  Helpers
     * ========================*/

    private function throttleKey(string $ip, string $identifier, string $ua = ''): string
    {
        // Use xxh3 if available (PHP 8.3+), otherwise fallback to sha256
        $algo = \in_array('xxh3', hash_algos(), true) ? 'xxh3' : 'sha256';
        $uaKey = substr(hash($algo, $ua ?: 'na'), 0, 10);
        return "{$ip}|auth|{$identifier}|{$uaKey}";
    }

    private function lockoutMessage(int $seconds, int $maxAttempts): string
    {
        if ($seconds >= 60) {
            $mins = (int) floor($seconds / 60);
            $secs = $seconds % 60;
            return "Too many attempts. Try again in {$mins} minute" . ($mins === 1 ? '' : 's') .
                ($secs ? " and {$secs} second" . ($secs === 1 ? '' : 's') : '') . '.';
        }
        return "Too many attempts. Try again in {$seconds} second" . ($seconds === 1 ? '' : 's') . '.';
    }

    /**
     * Resolve the actual users table name the app should use.
     * - Honors User::getTable()
     * - Falls back to 'users'
     */
    private function usersTable(): string
    {
        try {
            return (new User())->getTable() ?: 'users';
        } catch (\Throwable $e) {
            return 'users';
        }
    }

    /**
     * True if the QueryException is a "base table not found" (SQLSTATE 42S02).
     */
    private function isMissingTableError(QueryException $e): bool
    {
        // MySQL: SQLSTATE[42S02] Base table or view not found
        return $e->getCode() === '42S02' || Str::contains($e->getMessage(), 'SQLSTATE[42S02]');
    }

    /**
     * True if the QueryException indicates a unique key violation (for friendly re-tries).
     */
    private function isUniqueConstraintError(QueryException $e): bool
    {
        // MySQL duplicate entry: 23000 / 1062
        return (string) $e->getCode() === '23000' || Str::contains($e->getMessage(), 'Duplicate entry');
    }

    /**
     * Split a full name "First Last" into [first, last].
     */
    private function splitName(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return [null, null];
        }
        $parts = preg_split('/\s+/', $fullName, 2);
        $first = $parts[0] ?? null;
        $last  = $parts[1] ?? null;
        return [$first, $last];
    }
}
