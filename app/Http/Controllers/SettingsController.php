<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Setting;

class SettingsController extends Controller
{
    /**
     * Display the Settings overview page.
     */
    public function index()
    {
        // Load the first settings record (create one if none exists in the view)
        $settings = Setting::first();

        // ✅ Point to the correct blade under /resources/views/settings/settings.blade.php
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

        // ✅ Redirect to the correct named route
        return redirect()->route('settings.index')->with('success', 'Settings saved successfully!');
    }

    /**
     * Display the account settings form.
     */
    public function account()
    {
        // Blade: /resources/views/settings/account.blade.php
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
            $path = $request->file('photo')->store('profile_photos', 'public');
            $user->photo = $path;
        }

        // Update password if provided
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('settings.account')->with('success', 'Account updated successfully!');
    }
}
