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
  .field{
    border:1px solid rgba(0,0,0,.15);
    border-radius:.75rem;
    padding:.5rem .75rem;
    background:#fff;
    color:#111;
  }
  .btn{
    display:inline-flex;
    align-items:center;
    gap:.5rem;
    border:1px solid rgba(0,0,0,.15);
    border-radius:.75rem;
    padding:.5rem .9rem;
    background:#f9f9f9;
    color:#111;
    font-weight:500;
    font-size:.875rem;
  }
  .btn:hover{background:#f1f1f1}
  .btn-danger{
    border-color:rgba(239,68,68,.8);
    color:#b91c1c;
    background:rgba(254,226,226,.85);
  }
  .btn-danger:hover{
    background:rgba(252,165,165,.95);
  }
  .btn-primary{
    background:linear-gradient(135deg,#4f46e5,#2563eb);
    color:#fff;
    border-color:transparent;
  }
  .btn-primary:hover{
    filter:brightness(.97);
  }
  .alert{
    border-radius:.75rem;
    padding:.5rem .75rem;
    font-size:.8rem;
    margin-bottom:.75rem;
  }
  .alert-success{
    background:rgba(22,163,74,.08);
    border:1px solid rgba(22,163,74,.6);
    color:#166534;
  }
  .alert-error{
    background:rgba(239,68,68,.08);
    border:1px solid rgba(239,68,68,.7);
    color:#b91c1c;
  }
</style>
@endsection

@section('content')
<div class="p-6">
  <div class="max-w-3xl mx-auto glass-card">

    <div class="flex items-center justify-between mb-3">
      <h2 class="text-xl font-bold">Edit Employee</h2>
      <span class="text-xs text-gray-500">
        ID: EMP{{ str_pad($employee->id, 4, '0', STR_PAD_LEFT) }}
      </span>
    </div>

    {{-- Flash messages --}}
    @if(session('ok'))
      <div class="alert alert-success">
        {{ session('ok') }}
      </div>
    @endif

    {{-- Validation errors --}}
    @if($errors->any())
      <div class="alert alert-error">
        {{ $errors->first() }}
      </div>
    @endif

    {{-- UPDATE FORM --}}
    <form method="POST"
          action="{{ route('employees.update', $employee->id) }}"
          enctype="multipart/form-data"
          class="space-y-4">
      @csrf
      @method('PUT')

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-gray-600">First name</label>
          <input name="first_name"
                 class="field w-full"
                 value="{{ old('first_name',$employee->first_name) }}"
                 required>
          @error('first_name')
            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
          @enderror
        </div>

        <div>
          <label class="text-xs text-gray-600">Last name</label>
          <input name="last_name"
                 class="field w-full"
                 value="{{ old('last_name',$employee->last_name) }}"
                 required>
          @error('last_name')
            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
          @enderror
        </div>

        <div>
          <label class="text-xs text-gray-600">Email</label>
          <input type="email"
                 name="email"
                 class="field w-full"
                 value="{{ old('email',$employee->email) }}"
                 required>
          @error('email')
            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
          @enderror
        </div>

        <div>
          <label class="text-xs text-gray-600">Username</label>
          <input name="username"
                 class="field w-full"
                 value="{{ old('username',$employee->username) }}"
                 required>
          @error('username')
            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
          @enderror
        </div>

        <div>
          <label class="text-xs text-gray-600">Position</label>
          <input name="position"
                 class="field w-full"
                 value="{{ old('position',$employee->position) }}"
                 placeholder="e.g., Production Supervisor">
          @error('position')
            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
          @enderror
        </div>

        <div>
          <label class="text-xs text-gray-600">Status</label>
          <select name="status" class="field w-full">
            <option value="active"   @selected(old('status',$employee->status)==='active')>Active</option>
            <option value="inactive" @selected(old('status',$employee->status)==='inactive')>Inactive</option>
          </select>
          @error('status')
            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
          @enderror
        </div>

        <div>
          <label class="text-xs text-gray-600">Avatar</label>
          <input type="file"
                 name="avatar"
                 accept="image/*"
                 class="field w-full">
          @error('avatar')
            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
          @enderror

          @if($employee->avatar_url)
            <label class="inline-flex items-center gap-1 mt-2 text-xs text-gray-600">
              <input type="checkbox" name="remove_avatar" value="1">
              Remove current avatar
            </label>
          @endif
        </div>
      </div>

      @if($employee->avatar_url)
        <div class="pt-1">
          <div class="text-xs text-gray-600 mb-1">Current avatar</div>
          <img src="{{ $employee->avatar_url }}"
               class="h-16 w-16 rounded-full object-cover ring-1 ring-black/10"
               alt="Avatar">
        </div>
      @endif

      <div class="flex items-center justify-between pt-4">
        <a href="{{ route('employees.show', $employee->id) }}" class="btn">
          ← Cancel
        </a>

        <button type="submit" class="btn btn-primary">
          Save changes
        </button>
      </div>
    </form>

    {{-- DELETE FORM (separate, not nested) --}}
    <div class="mt-4 flex justify-end">
      <form method="POST"
            action="{{ route('employees.destroy', $employee->id) }}"
            onsubmit="return confirm('Delete this employee? This cannot be undone.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">
          Delete
        </button>
      </form>
    </div>
  </div>
</div>
@endsection
