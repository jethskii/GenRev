@extends('layout.mainlayout')

@section('content')
<div class="p-6 text-white">
    <h1 class="text-2xl font-bold mb-6">Settings</h1>

    <div class="bg-dark-bg p-6 rounded-lg shadow-md">
        <div class="bg-sidebar p-6 rounded-lg space-y-6">
            <a href="{{ route('settings.account') }}" class="block hover:bg-dark-field p-2 rounded transition">
            <h2 class="text-lg font-semibold text-white">Account Settings</h2>
            <p class="text-gray-400 text-sm">Change username or password</p>
        </a>


            <div>
                <h2 class="text-lg font-semibold">User Management</h2>
                <p class="text-gray-400 text-sm">Manage user accounts and permissions</p>
            </div>

            <div>
                <h2 class="text-lg font-semibold">Notifications</h2>
                <p class="text-gray-400 text-sm">Set preferences for notifications</p>
            </div>

            <div>
                <h2 class="text-lg font-semibold">Appearance</h2>
                <p class="text-gray-400 text-sm">Customize the application's look and feel</p>
            </div>
        </div>
    </div>
</div>
@endsection
