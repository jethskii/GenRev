@extends('layout.mainlayout')

@section('content')
<div class="p-6 text-white">
    <div class="bg-black/40 p-8 rounded-lg shadow-md max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold mb-8 text-gray-200">Account Settings</h1>

        @if(session('success'))
            <div class="bg-green-600 text-white px-4 py-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('settings.account.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-5">
                <label class="block text-gray-300 font-medium mb-1">Username</label>
                <input type="text" name="username" value="{{ old('username', auth()->user()->name) }}"
                    class="w-full bg-dark-field text-white rounded px-4 py-2 focus:outline-none">
            </div>

            <div class="mb-5">
                <label class="block text-gray-300 font-medium mb-1">Website</label>
                <input type="url" name="website" placeholder="https://example.com"
                    value="{{ old('website', auth()->user()->website) }}"
                    class="w-full bg-dark-field text-white rounded px-4 py-2 focus:outline-none">
            </div>

            <div class="mb-5">
                <label class="block text-gray-300 font-medium mb-1">Your Photo</label>
                <input type="file" name="photo"
                    class="w-full bg-dark-field text-white rounded px-4 py-2 file:bg-gray-700 file:text-white file:rounded file:px-3 file:py-1">
            </div>

            <div class="mb-5">
                <label class="block text-gray-300 font-medium mb-1">Your Bio</label>
                <textarea name="bio" rows="4" placeholder="Write a short introduction..."
                    class="w-full bg-dark-field text-white rounded px-4 py-2 focus:outline-none">{{ old('bio', auth()->user()->bio) }}</textarea>
            </div>

            <div class="mb-5">
                <label class="block text-gray-300 font-medium mb-1">Job Title</label>
                <input type="text" name="job_title" placeholder="e.g. Product Designer"
                    value="{{ old('job_title', auth()->user()->job_title) }}"
                    class="w-full bg-dark-field text-white rounded px-4 py-2 focus:outline-none">
            </div>

            <div class="mb-5">
                <label class="block text-gray-300 font-medium mb-1">Alternative Contact Email</label>
                <input type="email" name="alt_email" placeholder="example@domain.com"
                    value="{{ old('alt_email', auth()->user()->alt_email) }}"
                    class="w-full bg-dark-field text-white rounded px-4 py-2 focus:outline-none">
            </div>

            <div class="mt-6">
                <button type="submit"
                    class="bg-green-700 hover:bg-green-600 text-white px-5 py-2 rounded-lg transition">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
