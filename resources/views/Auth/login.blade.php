@extends('layout.mainlayout')

@section('content')
<div class="flex justify-center items-center min-h-screen bg-dark text-white">
    <div class="bg-dark-bg p-8 rounded-lg shadow-md w-full max-w-md border border-dark-line">
        <h2 class="text-2xl font-bold text-center mb-6">Admin Login</h2>

        @if(session('error'))
            <div class="bg-red-500 text-white p-3 mb-4 rounded-md text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="bg-green-500 text-white p-3 mb-4 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium mb-1">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="w-full px-4 py-2 rounded-md bg-[#2d3b2e] text-white border border-dark-line focus:outline-none focus:ring-2 focus:ring-armygreen"
                >
                @error('email')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
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

            <button
                type="submit"
                class="w-full bg-armygreen hover:bg-opacity-90 transition-colors py-2 rounded-md text-white font-semibold"
            >
                Login
            </button>

            <p class="text-sm mt-4 text-center">
                Don’t have an account?
                <a href="{{ route('register') }}" class="text-armygreen underline hover:text-green-400">Register here</a>
            </p>
        </form>
    </div>
</div>
@endsection
