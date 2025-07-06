<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Setting;

class SettingsController extends Controller
{
    /**
     * Display general settings form.
     */
    public function index()
    {
        $settings = Setting::first(); // Load the first settings record
        return view('settings', compact('settings'));
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

        $settings = Setting::firstOrNew(); // Create or get existing
        $settings->fill($validated);
        $settings->save();

        return redirect()->route('settings')->with('success', 'Settings saved successfully!');
    }

    /**
     * Display the account settings form.
     */
    public function account()
    {
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
        $user->name       = $request->username;
        $user->website    = $request->website;
        $user->bio        = $request->bio;
        $user->job_title  = $request->job_title;
        $user->alt_email  = $request->alt_email;

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
