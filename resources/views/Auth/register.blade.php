@extends('layout.mainlayout')

@section('content')
<div class="flex justify-center items-center min-h-screen bg-dark text-white">
    <div class="bg-dark-bg p-8 rounded-lg shadow-md w-full max-w-md border border-dark-line">
        <h2 class="text-2xl font-bold text-center mb-6">Register New Account</h2>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="bg-green-500 text-white p-3 mb-4 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Error Message --}}
        @if(session('error'))
            <div class="bg-red-500 text-white p-3 mb-4 rounded-md text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('register.submit') }}">
            @csrf

            {{-- Full Name --}}
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium mb-1">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    class="w-full px-4 py-2 rounded-md bg-[#2d3b2e] text-white border border-dark-line focus:outline-none focus:ring-2 focus:ring-armygreen"
                >
                @error('name')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium mb-1">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="w-full px-4 py-2 rounded-md bg-[#2d3b2e] text-white border border-dark-line focus:outline-none focus:ring-2 focus:ring-armygreen"
                >
                @error('email')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium mb-1">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="w-full px-4 py-2 rounded-md bg-[#2d3b2e] text-white border border-dark-line focus:outline-none focus:ring-2 focus:ring-armygreen"
                >
                @error('password')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div class="mb-4">
                <label for="password_confirmation" class="block text-sm font-medium mb-1">Confirm Password</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    class="w-full px-4 py-2 rounded-md bg-[#2d3b2e] text-white border border-dark-line focus:outline-none focus:ring-2 focus:ring-armygreen"
                >
            </div>

            {{-- User Role Dropdown --}}
            <div class="mb-6">
                <label for="role" class="block text-sm font-medium mb-1">User Role</label>
                <select
                    id="role"
                    name="role"
                    class="w-full px-4 py-2 rounded-md bg-[#2d3b2e] text-white border border-dark-line focus:outline-none focus:ring-2 focus:ring-armygreen"
                    required
                >
                    <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
                @error('role')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <button
                type="submit"
                class="w-full bg-armygreen hover:bg-opacity-90 transition-colors py-2 rounded-md text-white font-semibold"
            >
                Register
            </button>

            {{-- Switch to Login --}}
            <p class="text-sm mt-4 text-center">
                Already have an account?
                <a href="{{ route('login') }}" class="text-armygreen underline hover:text-green-400">Login here</a>
            </p>
        </form>
    </div>
</div>
@endsection
