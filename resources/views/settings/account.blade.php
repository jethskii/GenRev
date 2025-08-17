@extends('layout.mainlayout')

@section('content')
<div class="p-6 text-white">
    <div class="glass max-w-3xl mx-auto p-8 rounded-xl border border-dark-line shadow-xl backdrop-blur-sm">
        <h1 class="text-3xl font-bold mb-8 text-white">Account Settings</h1>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-2 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('settings.account.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Username --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-white mb-1">Username</label>
                <input type="text" name="username" value="{{ old('username', auth()->user()->name) }}"
                    class="w-full bg-white text-black placeholder-gray-500 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-green-500">
            </div>

            {{-- Website --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-white mb-1">Website</label>
                <input type="url" name="website" placeholder="https://example.com"
                    value="{{ old('website', auth()->user()->website) }}"
                    class="w-full bg-white text-black placeholder-gray-500 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-green-500">
            </div>

            {{-- Profile Photo --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-white mb-1">Your Photo</label>
                <input type="file" name="photo"
                    class="w-full text-black bg-white file:bg-[#1F4B2C] file:text-white file:rounded file:px-4 file:py-1.5 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-green-500">
            </div>

            {{-- Bio --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-white mb-1">Your Bio</label>
                <textarea name="bio" rows="4" placeholder="Write a short introduction..."
                    class="w-full bg-white text-black placeholder-gray-500 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-green-500">{{ old('bio', auth()->user()->bio) }}</textarea>
            </div>

            {{-- Job Title --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-white mb-1">Job Title</label>
                <input type="text" name="job_title" placeholder="e.g. Product Designer"
                    value="{{ old('job_title', auth()->user()->job_title) }}"
                    class="w-full bg-white text-black placeholder-gray-500 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-green-500">
            </div>

            {{-- Alternative Email --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-white mb-1">Alternative Contact Email</label>
                <input type="email" name="alt_email" placeholder="example@domain.com"
                    value="{{ old('alt_email', auth()->user()->alt_email) }}"
                    class="w-full bg-white text-black placeholder-gray-500 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-green-500">
            </div>

            {{-- Save Button --}}
            <div class="mt-6">
                <button type="submit"
                    class="bg-armygreen hover:bg-[#9dc153] text-[#1F4B2C] font-semibold px-6 py-2 rounded-lg shadow transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
