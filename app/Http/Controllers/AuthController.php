<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /* =========================
     |  Auth views (GET)
     * ========================*/

    /** Show login form */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /** Show register form */
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
     * Handle login
     * Accepts either an email (case-insensitive) OR an employee username.
     * Your login form can keep the input name "email" — we’ll detect if it’s actually a username.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'string'], // may be email or username
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        // Basic rate limiting by IP + identifier
        $identifier = Str::lower(trim($data['email']));
        $throttleKey = Str::lower($request->ip() . '|' . $identifier);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => "Too many attempts. Try again in {$seconds} seconds.",
                ]);
        }

        $remember = (bool)($data['remember'] ?? false);
        $raw = trim($data['email']);

        // 1) If it looks like an email, normalize and try direct login
        if (str_contains($raw, '@')) {
            $email = Str::lower($raw);

            if (Auth::attempt(['email' => $email, 'password' => $data['password']], $remember)) {
                RateLimiter::clear($throttleKey);
                $request->session()->regenerate();
                return redirect()->intended(route('dashboard'));
            }
        } else {
            // 2) Otherwise treat as username: resolve to a user via employees.username
            $employee = Employee::query()
                ->whereRaw('LOWER(username) = ?', [Str::lower($raw)])
                ->first();

            if ($employee && $employee->user_id) {
                $user = User::find($employee->user_id);
                if ($user) {
                    // attempt using the resolved email
                    if (Auth::attempt(
                        ['email' => Str::lower($user->email), 'password' => $data['password']],
                        $remember
                    )) {
                        RateLimiter::clear($throttleKey);
                        $request->session()->regenerate();
                        return redirect()->intended(route('dashboard'));
                    }
                }
            }
        }

        // Increment attempts on failure
        RateLimiter::hit($throttleKey, 60); // decay in 60s

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'Invalid credentials.']);
    }

    /**
     * Handle registration (also creates/links Employee)
     * Assumes employees.username is unique and users.email is unique.
     */
    public function register(Request $request)
    {
        // Allow a single "name" and split if needed
        $fullName = (string) $request->input('name', '');
        $request->merge([
            'first_name' => $request->input('first_name') ?: ($fullName ? explode(' ', $fullName, 2)[0] : null),
            'last_name'  => $request->input('last_name')  ?: ($fullName ? (explode(' ', $fullName, 2)[1] ?? '') : null),
            'username'   => $request->input('username')   ?: $request->input('email'),
        ]);

        $validated = $request->validate([
            'name'       => ['nullable','string','max:255'],
            'first_name' => ['required','string','max:120'],
            'last_name'  => ['required','string','max:120'],
            'position'   => ['nullable','string','max:160'],
            'username'   => ['required','string','max:120','unique:employees,username'],
            'email'      => ['required','email','unique:users,email'],
            'password'   => ['required','string','min:6','confirmed'],
            'role'       => ['required','in:admin,sales,inventory'],
        ]);

        DB::transaction(function () use ($validated) {
            $email = Str::lower($validated['email']);
            $username = Str::lower($validated['username']);

            // 1) Create auth user
            $user = User::create([
                'name'     => trim(($validated['first_name'] ?? '').' '.($validated['last_name'] ?? '')),
                'email'    => $email,
                'password' => Hash::make($validated['password']),
                'role'     => Str::lower($validated['role']),
            ]);

            // 2) Link to existing employee (by email) or create new
            $existing = Employee::whereRaw('LOWER(email) = ?', [$email])->first();

            if ($existing) {
                if (!$existing->user_id) {
                    $existing->user_id = $user->id;
                }
                if (!$existing->username) {
                    $existing->username = $username;
                }
                $existing->status = $existing->status ?: 'active';
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
    }

    /** Logout user */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}
