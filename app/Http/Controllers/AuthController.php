<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /* =========================
     |  Auth Views (GET)
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
     * Handle login (email or employee username)
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'string'], // may be email or username
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $identifier  = Str::lower(trim($data['email']));
        $throttleKey = $request->ip() . '|auth|' . $identifier;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        $remember = (bool) ($data['remember'] ?? false);
        $raw      = trim($data['email']);
        $creds    = null;

        // Login by email
        if (str_contains($raw, '@')) {
            $creds = ['email' => Str::lower($raw), 'password' => $data['password']];
        } else {
            // Login by username via Employee → User
            $employee = Employee::query()
                ->whereRaw('LOWER(username) = ?', [Str::lower($raw)])
                ->first();

            if ($employee?->user_id) {
                $user = User::find($employee->user_id);
                if ($user) {
                    $creds = ['email' => Str::lower($user->email), 'password' => $data['password']];
                }
            }
        }

        if ($creds && Auth::attempt($creds, $remember)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($throttleKey, 60); // decay in 60s

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'Invalid credentials.']);
    }

    /**
     * Register a user and link/create Employee
     */
    public function register(Request $request)
    {
        // Allow single "name" to auto-split
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
            $email    = Str::lower($validated['email']);
            $username = Str::lower($validated['username']);

            // Create auth user (password hashed by model cast)
            $user = User::create([
                'name'     => trim(($validated['first_name'] ?? '').' '.($validated['last_name'] ?? '')),
                'email'    => $email,
                'password' => $validated['password'], // 'hashed' cast will hash
                'role'     => Str::lower($validated['role']),
            ]);

            // Link to existing employee by email or create
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
