<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\UserSetting;
use App\Models\LoginActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    /**
     * Display the Settings overview page.
     */
    public function index()
    {
        // Load the first settings record (create one if none exists in the view)
        $settings = Setting::first();

        // Blade: resources/views/settings/settings.blade.php
        return view('settings.settings', compact('settings'));
    }

    /**
     * Store or update general settings.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'phone'        => 'nullable|string|max:50',
            'address'      => 'nullable|string|max:500',
        ]);

        // Create or update a single settings row
        $settings = Setting::firstOrNew([]);
        $settings->fill($validated)->save();

        return redirect()
            ->route('settings.index')
            ->with('success', 'Settings saved successfully!');
    }

    /**
     * Display the account settings form.
     */
    public function account()
    {
        // Blade: resources/views/settings/account.blade.php
        return view('settings.account');
    }

    /**
     * Update user account settings.
     */
    public function updateAccount(Request $request)
    {
        $request->validate([
            'username'   => 'required|string|max:255',
            'password'   => 'nullable|string|min:6|confirmed',
            'website'    => 'nullable|url|max:255',
            'bio'        => 'nullable|string|max:500',
            'job_title'  => 'nullable|string|max:100',
            'alt_email'  => 'nullable|email|max:255',
            'photo'      => 'nullable|image|max:2048',
        ]);

        $user = Auth::user();

        $user->name      = $request->username;
        $user->website   = $request->website;
        $user->bio       = $request->bio;
        $user->job_title = $request->job_title;
        $user->alt_email = $request->alt_email;

        // Handle profile photo upload
        if ($request->hasFile('photo')) {
            $path       = $request->file('photo')->store('profile_photos', 'public');
            $user->photo = $path;
        }

        // Update password if provided
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()
            ->route('settings.account')
            ->with('success', 'Account updated successfully!');
    }

    // -----------------------------
    // Appearance
    // -----------------------------

    /**
     * Show the Appearance settings page.
     */
    public function appearance()
    {
        $userId     = Auth::id();
        $appearance = UserSetting::appearanceFor($userId); // ['theme','accent','font_style']

        // Blade: resources/views/settings/appearance.blade.php
        return view('settings.appearance', compact('appearance'));
    }

    /**
     * Save Appearance settings.
     */
    public function appearanceUpdate(Request $request)
    {
        $data = $request->validate([
            'theme'      => 'required|in:light,dark,system',
            'accent'     => 'required|string|max:20',
            'font_style' => 'required|in:default,rounded,mono',
        ]);

        UserSetting::putAppearance(Auth::id(), $data);

        return redirect()
            ->route('settings.appearance')
            ->with('status', 'Appearance saved ✅');
    }

    /**
     * Reset per-user Appearance.
     */
    public function appearanceReset()
    {
        UserSetting::resetAppearance(Auth::id());

        return redirect()
            ->route('settings.appearance')
            ->with('status', 'Appearance reset 🔄');
    }

    /**
     * Defaults for appearance settings.
     */
    private function appearanceDefaults(): array
    {
        return [
            'theme'      => 'light',
            'accent'     => '#3b82f6',
            'font_style' => 'default',
        ];
    }

    // -----------------------------
    // Login Activity / Log Book
    // -----------------------------

    /**
     * Show the Login Log Book with filters and search.
     *
     * Route name in Blade: route('settings.login-activity')
     */
    public function loginActivity(Request $request)
    {
        $status = $request->input('status');            // null | 'success' | 'failed'
        $search = trim($request->input('search', ''));  // name or email

        $query = LoginActivity::query()
            ->with('user');

        // Filter by status if provided
        if (in_array($status, ['success', 'failed'], true)) {
            $query->where('succeeded', $status === 'success');
        }

        // Filter by search (name/email on related user)
        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Latest login first; paginate & keep query params
        $activities = $query
            ->orderByDesc('login_at')
            ->paginate(15)
            ->appends($request->query());

        return view('settings.login-activity', compact(
            'activities',
            'status',
            'search'
        ));
    }
}
