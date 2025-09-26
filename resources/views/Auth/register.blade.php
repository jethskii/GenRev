@extends('layout.mainlayout')

@section('content')
<div class="flex justify-center items-center min-h-screen bg-dark text-white px-4">
  <div class="bg-black/40 backdrop-blur-md border border-dark-line p-8 rounded-lg shadow-xl w-full max-w-md">

    <h2 class="text-3xl font-bold text-center mb-6">Register New Account</h2>

    {{-- Flash messages --}}
    @if(session('success'))
      <div class="bg-green-500 text-white px-4 py-2 mb-4 rounded text-sm">
        {{ session('success') }}
      </div>
    @endif
    @if(session('error'))
      <div class="bg-red-500 text-white px-4 py-2 mb-4 rounded text-sm">
        {{ session('error') }}
      </div>
    @endif

    <form method="POST" action="{{ route('register.submit') }}">
      @csrf

      {{-- Full Name (controller splits to first/last if not provided separately) --}}
      <div class="mb-4">
        <label for="name" class="block text-sm font-semibold mb-1">Full Name</label>
        <input
          type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
          class="w-full px-4 py-2 bg-white text-black rounded-md border border-gray-300 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-armygreen">
        @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
      </div>

      {{-- Username (used for username login via Employee) --}}
      <div class="mb-4">
        <label for="username" class="block text-sm font-semibold mb-1">Username</label>
        <input
          type="text" id="username" name="username" value="{{ old('username') }}"
          class="w-full px-4 py-2 bg-white text-black rounded-md border border-gray-300 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-armygreen">
        <small class="text-gray-400">If left blank, your email will be used as username.</small>
        @error('username') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
      </div>

      {{-- Email --}}
      <div class="mb-4">
        <label for="email" class="block text-sm font-semibold mb-1">Email Address</label>
        <input
          type="email" id="email" name="email" value="{{ old('email') }}" required
          class="w-full px-4 py-2 bg-white text-black rounded-md border border-gray-300 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-armygreen">
        @error('email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
      </div>

      {{-- Password --}}
      <div class="mb-4">
        <label for="password" class="block text-sm font-semibold mb-1">Password</label>
        <input
          type="password" id="password" name="password" required
          class="w-full px-4 py-2 bg-white text-black rounded-md border border-gray-300 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-armygreen">
        @error('password') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
      </div>

      {{-- Confirm Password --}}
      <div class="mb-4">
        <label for="password_confirmation" class="block text-sm font-semibold mb-1">Confirm Password</label>
        <input
          type="password" id="password_confirmation" name="password_confirmation" required
          class="w-full px-4 py-2 bg-white text-black rounded-md border border-gray-300 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-armygreen">
      </div>

      {{-- Position (optional) --}}
      <div class="mb-4">
        <label for="position" class="block text-sm font-semibold mb-1">Position (optional)</label>
        <input
          type="text" id="position" name="position" value="{{ old('position') }}"
          class="w-full px-4 py-2 bg-white text-black rounded-md border border-gray-300 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-armygreen">
        @error('position') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
      </div>

      {{-- Role (must match controller validation: admin|sales|inventory) --}}
      <div class="mb-6">
        <label for="role" class="block text-sm font-semibold mb-1">User Role</label>
        <select
          id="role" name="role" required
          class="w-full px-4 py-2 bg-white text-black rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-armygreen">
          <option value="admin"     {{ old('role')==='admin'?'selected':'' }}>Admin</option>
          <option value="sales"     {{ old('role')==='sales'?'selected':'' }}>Sales</option>
          <option value="inventory" {{ old('role')==='inventory'?'selected':'' }}>Inventory</option>
        </select>
        @error('role') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
      </div>

      <button type="submit" class="w-full bg-armygreen hover:bg-[#b7d94c] text-[#1F4B2C] font-semibold py-2 rounded-md shadow transition">
        Register
      </button>

      <p class="text-sm mt-4 text-center">
        Already have an account?
        <a href="{{ route('login') }}" class="text-armygreen underline hover:text-green-400">Login here</a>
      </p>
    </form>
  </div>
</div>
@endsection
