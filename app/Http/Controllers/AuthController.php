<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\LoginActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Http;   // you can keep or remove this later
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;   // ✅ important
use Illuminate\Support\Facades\Validator; // ✅ added for requestOtp()
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

    public function showRegisterForm(Request $request)
    {
        // if already logged in, redirect away
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /* =========================
     |  Login (POST)
     * ========================*/
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $raw        = trim($data['email']);
        $identifier = Str::lower($raw);
        $remember   = (bool) ($data['remember'] ?? false);

        $maxAttempts  = (int) (config('auth.max_attempts', env('AUTH_MAX_ATTEMPTS', self::DEFAULT_MAX_ATTEMPTS)));
        $decaySeconds = (int) (config('auth.decay_seconds', env('AUTH_DECAY_SECONDS', self::DEFAULT_DECAY_SECONDS)));

        $throttleKey = $this->throttleKey((string) $request->ip(), $identifier, (string) $request->userAgent());
        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->logLoginAttempt(
                user: null,
                identifier: $raw,
                request: $request,
                success: false,
                reason: 'too_many_attempts'
            );

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => $this->lockoutMessage($seconds, $maxAttempts)]);
        }

        try {
            $credentials = null;
            $loginUser   = null;

            if (str_contains($raw, '@')) {
                // Email login (case-insensitive)
                $emailLower = Str::lower($raw);
                $credentials = [
                    'email'     => $emailLower,
                    'password'  => $data['password'],
                    'is_active' => true,
                ];

                $loginUser = User::whereRaw('LOWER(email) = ?', [$emailLower])->first();
            } else {
                // Username login via employees.username -> users.email
                $employee = Employee::query()
                    ->whereRaw('LOWER(username) = ?', [$identifier])
                    ->first();

                if ($employee && isset($employee->status)) {
                    $status = strtolower((string) $employee->status);

                    if ($status !== 'active') {
                        RateLimiter::hit($throttleKey, $decaySeconds);

                        $this->logLoginAttempt(
                            user: null,
                            identifier: $raw,
                            request: $request,
                            success: false,
                            reason: 'employee_inactive'
                        );

                        return back()
                            ->withInput($request->only('email', 'remember'))
                            ->withErrors([
                                'email' => 'This employee account is inactive. Please contact the administrator.',
                            ]);
                    }
                }

                if ($employee?->user_id) {
                    $loginUser = User::find($employee->user_id);
                    if ($loginUser) {
                        $credentials = [
                            'email'     => Str::lower($loginUser->email),
                            'password'  => $data['password'],
                            'is_active' => true,
                        ];
                    }
                }
            }

            if ($credentials && Auth::attempt($credentials, $remember)) {
                RateLimiter::clear($throttleKey);
                $request->session()->regenerate();

                /** @var \App\Models\User|null $authUser */
                $authUser = Auth::user();

                if ($authUser && property_exists($authUser, 'is_active') && !$authUser->is_active) {
                    $this->logLoginAttempt(
                        user: $authUser,
                        identifier: $raw,
                        request: $request,
                        success: false,
                        reason: 'user_inactive'
                    );

                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return back()
                        ->withInput($request->only('email', 'remember'))
                        ->withErrors(['email' => 'This account is disabled.']);
                }

                if ($authUser) {
                    $linkedEmployee = Employee::where('user_id', $authUser->id)->first();
                    if ($linkedEmployee && strtolower((string) $linkedEmployee->status) !== 'active') {
                        $this->logLoginAttempt(
                            user: $authUser,
                            identifier: $raw,
                            request: $request,
                            success: false,
                            reason: 'linked_employee_inactive'
                        );

                        Auth::logout();
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();
                        return back()
                            ->withInput($request->only('email', 'remember'))
                            ->withErrors([
                                'email' => 'This employee account is inactive. Please contact the administrator.',
                            ]);
                    }
                }

                // Post-login: normalize role + update last_login_at
                try {
                    if ($authUser) {
                        $newRole = $this->normalizeRole((string) ($authUser->role ?? ''));
                        $updates = ['last_login_at' => now()];
                        if ($newRole !== ($authUser->role ?? '')) {
                            $updates['role'] = $newRole;
                        }
                        if (method_exists($authUser, 'forceFill')) {
                            $authUser->forceFill($updates)->saveQuietly();
                        } else {
                            $authUser->fill($updates)->saveQuietly();
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore soft failures
                }

                $this->logLoginAttempt(
                    user: $authUser,
                    identifier: $raw,
                    request: $request,
                    success: true,
                    reason: null
                );

                return redirect()->intended(route('dashboard'));
            }

            // Failed attempt
            RateLimiter::hit($throttleKey, $decaySeconds);

            $this->logLoginAttempt(
                user: $loginUser,
                identifier: $raw,
                request: $request,
                success: false,
                reason: 'invalid_credentials'
            );

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Invalid credentials.']);

        } catch (QueryException $e) {
            if ($this->isMissingTableError($e)) {
                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors(['email' =>
                        'A required table is missing (users or employees). Run your migrations and clear caches.'
                    ]);
            }
            throw $e;
        }
    }

    /* =========================
     |  Register – STEP 1 (AJAX)
     |  /register/otp  -> requestOtp()
     * ========================*/
    public function requestOtp(Request $request)
    {
        // Split name into first / last
        $fullName = trim((string) $request->input('name', ''));
        [$firstAuto, $lastAuto] = $this->splitName($fullName);
        $request->merge([
            'first_name' => $firstAuto ?: null,
            'last_name'  => $lastAuto ?: null,
        ]);

        // Normalize fields
        $request->merge([
            'email'    => Str::lower((string) $request->input('email')),
            'username' => Str::lower((string) ($request->input('username') ?: $request->input('email'))),
            'role'     => Str::lower((string) $request->input('role')),
        ]);

        $usersTable = $this->usersTable();

        $validator = Validator::make($request->all(), [
            'name'       => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name'  => ['required', 'string', 'max:120'],
            'username'   => ['required', 'string', 'max:120', Rule::unique('employees', 'username')],
            'email'      => ['required', 'email', Rule::unique($usersTable, 'email')],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
            // 'masters admin' is deliberately excluded: it's not offered in the registration
            // form's role dropdown, and self-service OTP verification means anyone who could
            // submit this value directly (bypassing the dropdown) would get an unapproved admin
            // account. New admins are created by an existing admin via the Users management page.
            'role'       => ['required', Rule::in(['production manager', 'sales', 'inventory'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok'     => false,
                'message'=> 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $otp = (string) random_int(100000, 999999);

            // Store validated data + OTP in session
            $request->session()->put('pending_registration', [
                'data' => $validated,
                'otp'  => $otp,
            ]);
            $request->session()->put('otp_pending', true);

            // Send OTP via Laravel Mail
            $this->sendOtpToApplicant($otp, $validated);

            return response()->json([
                'ok'      => true,
                'message' => 'OTP has been sent to your email. Enter it below to finish registration.',
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to send registration OTP email', [
                'error' => $e->getMessage(),
            ]);

            $request->session()->forget(['pending_registration', 'otp_pending']);

            return response()->json([
                'ok'      => false,
                'message' => 'Unable to send OTP email. Please try again or contact support.',
            ], 500);
        }
    }

    /* =========================
     |  Register – STEP 2
     |  /register (Create Account)
     * ========================*/
    public function register(Request $request)
    {
        $pending = $request->session()->get('pending_registration');

        if (!$pending) {
            // User somehow skipped step 1
            return back()
                ->withErrors(['otp' => 'No pending registration found. Please send a new OTP first.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        // Validate only the OTP – all other data already validated in step 1
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        if (!hash_equals((string) $pending['otp'], (string) $request->input('otp'))) {
            return back()
                ->withErrors(['otp' => 'Invalid OTP. Please check the code sent to your email and try again.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $validated = $pending['data'];

        try {
            DB::transaction(function () use ($validated) {
                $email    = $validated['email'];
                $username = $validated['username'];
                $role     = $this->normalizeRole($validated['role']);

                $fullName = trim($validated['name'] ?? (($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? '')));

                /** @var \App\Models\User $user */
                $user = User::create([
                    'name'       => $fullName,
                    'email'      => $email,
                    'password'   => Hash::make($validated['password']),
                    'role'       => $role,
                    'is_active'  => true,
                ]);

                // Attach to existing employee by email; otherwise create
                /** @var \App\Models\Employee|null $existing */
                $existing = Employee::whereRaw('LOWER(email) = ?', [Str::lower($email)])->first();

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
                        'position'   => null,
                        'email'      => $email,
                        'username'   => $username,
                        'status'     => 'active',
                    ]);
                }
            });

            $request->session()->forget(['pending_registration', 'otp_pending']);

            return redirect()->route('login')
                ->with('success', 'Account created successfully. You can now log in.');

        } catch (QueryException $e) {
            if ($this->isMissingTableError($e)) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['email' =>
                        "A required table is missing. Ensure your auth and employees tables exist, then run:\n".
                        "php artisan migrate --force\n".
                        "php artisan config:clear && php artisan cache:clear && php artisan optimize:clear"
                    ]);
            }

            if ($this->isUniqueConstraintError($e)) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['email' => 'That email or username was just taken. Please try a different one.']);
            }

            throw $e;
        }
    }

    /* =========================
     |  Logout
     * ========================*/
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            try {
                $last = LoginActivity::where('user_id', $user->id)
                    ->whereNull('logout_at')
                    ->orderByDesc('login_at')
                    ->first();

                if ($last) {
                    $last->logout_at = now();
                    $last->save();
                }
            } catch (\Throwable $e) {
                // ignore logging issues
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }

    /* =========================
     |  Helpers
     * ========================*/

    /**
     * Send the OTP to the person registering, via Laravel Mail (Gmail SMTP).
     */
    private function sendOtpToApplicant(string $otp, array $validated): void
    {
        $name = $validated['name']
            ?? trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));

        $role  = $validated['role']  ?? '';
        $email = $validated['email'] ?? '';

        Mail::raw(
            "Hi {$name},\n\n" .
            "Use the code below to finish creating your GenRev account:\n\n" .
            "OTP: {$otp}\n\n" .
            "Role requested: {$role}\n\n" .
            "If you didn't request this, you can ignore this email.",
            function ($message) use ($email) {
                $message->to($email)
                        ->subject('Your GenRev Registration OTP');
            }
        );
    }

    private function normalizeRole(string $role): string
    {
        $rawLower = strtolower(trim($role));
        $norm     = preg_replace('/[^a-z]/', '', $rawLower);

        return match ($norm) {
            'mastersadmin', 'masteradmin', 'admin', 'administrator', 'superadmin', 'superadministrator' => 'masters admin',
            'productionmanager' => 'production manager',
            'sales'             => 'sales',
            'inventory'         => 'inventory',
            default             => (str_contains($norm, 'admin') ? 'masters admin' : ($rawLower ?: 'sales')),
        };
    }

    private function throttleKey(string $ip, string $identifier, string $ua = ''): string
    {
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

    private function usersTable(): string
    {
        try {
            return (new User())->getTable() ?: 'users';
        } catch (\Throwable $e) {
            return 'users';
        }
    }

    private function isMissingTableError(QueryException $e): bool
    {
        return $e->getCode() === '42S02' || Str::contains($e->getMessage(), 'SQLSTATE[42S02]');
    }

    private function isUniqueConstraintError(QueryException $e): bool
    {
        return (string) $e->getCode() === '23000' || Str::contains($e->getMessage(), 'Duplicate entry');
    }

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

    private function logLoginAttempt(?User $user, string $identifier, Request $request, bool $success, ?string $reason = null): void
    {
        try {
            LoginActivity::create([
                'user_id'    => $user?->id,
                'email'      => $user?->email ?: $identifier,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'login_at'   => now(),
                'succeeded'  => $success,
                'reason'     => $reason,
            ]);
        } catch (\Throwable $e) {
            // never break login flow because of logging issues
        }
    }
}
