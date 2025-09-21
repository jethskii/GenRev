@extends('layout.mainlayout')

@section('head')
<style>
  .glass-card{
    background:linear-gradient(180deg,rgba(255,255,255,.95),rgba(245,245,245,.9));
    backdrop-filter: blur(12px);
    border:1px solid rgba(0,0,0,.1);
    border-radius:1rem;
    padding:1.25rem;
    color:#111;
  }
  .field{border:1px solid rgba(0,0,0,.15);border-radius:.75rem;padding:.5rem .75rem;background:#fff;color:#111}
  .btn{display:inline-flex;align-items:center;gap:.5rem;border:1px solid rgba(0,0,0,.15);border-radius:.75rem;padding:.5rem .9rem;background:#f9f9f9;color:#111;font-weight:500}
  .btn:hover{background:#f1f1f1}
  .btn-dark{background:#111;color:#fff;border-color:#111}
  .btn-dark:hover{opacity:.9}
</style>
@endsection

@section('content')
<div class="p-6">
  <div class="max-w-3xl mx-auto glass-card">
    <h2 class="text-xl font-bold mb-4">Edit Employee</h2>

    <form method="POST" action="{{ route('employees.update', $employee->id) }}" enctype="multipart/form-data" class="space-y-4">
      @csrf @method('PUT')

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-gray-600">First name</label>
          <input name="first_name" class="field w-full" value="{{ old('first_name',$employee->first_name) }}" required>
          @error('first_name') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="text-xs text-gray-600">Last name</label>
          <input name="last_name" class="field w-full" value="{{ old('last_name',$employee->last_name) }}" required>
          @error('last_name') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="text-xs text-gray-600">Email</label>
          <input type="email" name="email" class="field w-full" value="{{ old('email',$employee->email) }}">
          @error('email') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="text-xs text-gray-600">Username</label>
          <input name="username" class="field w-full" value="{{ old('username',$employee->username) }}" required>
          @error('username') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="text-xs text-gray-600">Position</label>
          <input name="position" class="field w-full" value="{{ old('position',$employee->position) }}" placeholder="e.g., Production Supervisor">
          @error('position') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="text-xs text-gray-600">Status</label>
          <select name="status" class="field w-full">
            <option value="active"   @selected(old('status',$employee->status)==='active')>Active</option>
            <option value="inactive" @selected(old('status',$employee->status)==='inactive')>Inactive</option>
          </select>
          @error('status') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="text-xs text-gray-600">New Password (optional)</label>
          <input type="password" name="password" class="field w-full" minlength="6" placeholder="Leave blank to keep current">
          @error('password') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="text-xs text-gray-600">Avatar</label>
          <input type="file" name="avatar" accept="image/*" class="field w-full">
          @error('avatar') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
      </div>

      @if($employee->avatar_url)
        <div class="pt-1">
          <div class="text-xs text-gray-600 mb-1">Current avatar</div>
          <img src="{{ $employee->avatar_url }}" class="h-16 w-16 rounded-full object-cover ring-1 ring-black/10" alt="Avatar">
        </div>
      @endif

      <div class="flex items-center justify-between pt-2">
        <a href="{{ route('employees.show', $employee->id) }}" class="btn">← Cancel</a>

        <div class="flex items-center gap-2">
          <form method="POST" action="{{ route('employees.destroy', $employee->id) }}"
                onsubmit="return confirm('Delete this employee? This cannot be undone.');">
            @csrf @method('DELETE')
            <button type="submit" class="btn">Delete</button>
          </form>
          <button type="submit" class="btn-dark">Save changes</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
