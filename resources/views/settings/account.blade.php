@extends('layout.mainlayout')

@section('content')
<div class="p-6 text-gray-900">
  <div class="max-w-3xl mx-auto bg-white border border-gray-200 shadow-sm rounded-2xl p-8">
    <h1 class="text-3xl font-bold mb-8">Account Settings</h1>

    @if(session('success'))
      <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
        {{ session('success') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
        <ul class="list-disc list-inside space-y-1">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('settings.account.update') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
      @csrf

      {{-- Username --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="username">Username</label>
        <input
          id="username"
          type="text"
          name="username"
          value="{{ old('username', auth()->user()->name) }}"
          class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-400"
        >
      </div>

      {{-- Website --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="website">Website</label>
        <input
          id="website"
          type="url"
          name="website"
          placeholder="https://example.com"
          value="{{ old('website', auth()->user()->website) }}"
          class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-400"
        >
        <p class="mt-1 text-xs text-gray-500">Use a full URL including https://</p>
      </div>

      {{-- Profile Photo --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="photo">Your Photo</label>
        <input
          id="photo"
          type="file"
          name="photo"
          accept="image/png,image/jpeg,image/webp"
          class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-gray-900 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-white hover:file:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-blue-400"
        >
        <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WebP. Max 4MB.</p>
      </div>

      {{-- Bio --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="bio">Your Bio</label>
        <textarea
          id="bio"
          name="bio"
          rows="4"
          placeholder="Write a short introduction..."
          class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-400"
        >{{ old('bio', auth()->user()->bio) }}</textarea>
      </div>

      {{-- Job Title --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="job_title">Job Title</label>
        <input
          id="job_title"
          type="text"
          name="job_title"
          placeholder="e.g., Product Designer"
          value="{{ old('job_title', auth()->user()->job_title) }}"
          class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-400"
        >
      </div>

      {{-- Alternative Email --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="alt_email">Alternative Contact Email</label>
        <input
          id="alt_email"
          type="email"
          name="alt_email"
          placeholder="example@domain.com"
          value="{{ old('alt_email', auth()->user()->alt_email) }}"
          class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-400"
        >
      </div>

      {{-- Actions --}}
      <div class="pt-2 flex items-center gap-3">
        <button type="submit" class="btn-primary bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-2 rounded-lg shadow-sm">
          Save Changes
        </button>
        <a href="{{ url()->previous() }}"
           class="btn-secondary bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow-sm">
          Cancel
        </a>
      </div>
    </form>
  </div>
</div>
@endsection
