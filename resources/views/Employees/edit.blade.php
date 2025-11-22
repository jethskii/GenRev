{{-- resources/views/employees/edit.blade.php --}}
@extends('layout.mainlayout')

@section('head')
<style>
  :root{
    --bg-body:#f5f5f6;
    --panel-bg:#fdfdfc;
    --border-soft:rgba(15,23,42,0.16);
    --shadow-main:0 10px 30px rgba(15,23,42,0.12);
    --accent-red:#b91c1c;
    --accent-yellow:#facc15;
    --accent-green:#16a34a;
    --text-main:#111827;
    --text-muted:#6b7280;
    --text-soft:#9ca3af;
  }

  body{
    font-family:system-ui,-apple-system,BlinkMacSystemFont,"SF Pro Text","Segoe UI",sans-serif;
  }

  /* Page wrapper */
  .glass-wrap{
    background:var(--panel-bg);
    border-radius:16px;
    border:1px solid var(--border-soft);
    box-shadow:var(--shadow-main);
    padding:1.5rem 1.5rem 1.6rem;
    color:var(--text-main);
  }

  /* Card */
  .glass-card{
    background:#ffffff;
    border-radius:14px;
    border:1px solid rgba(15,23,42,0.12);
    padding:1.5rem 1.75rem;
    box-shadow:0 10px 25px rgba(15,23,42,0.08);
    color:var(--text-main);
  }

  h2{
    color:var(--text-main) !important;
    font-size:18px;
    font-weight:800;
    letter-spacing:.03em;
  }
  label,strong{
    color:var(--text-main) !important;
  }
  p,.text-xs,.text-sm{
    color:var(--text-muted) !important;
  }

  /* ID Tag */
  .id-tag{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:4px 10px;
    border-radius:999px;
    border:1px solid rgba(220,38,38,0.45);
    background:#fee2e2;
    font-size:11px;
    color:#b91c1c;
    font-weight:600;
  }

  /* Fields */
  .field{
    border:1px solid rgba(15,23,42,0.18);
    border-radius:10px;
    padding:7px 10px;
    background:#ffffff;
    color:var(--text-main);
    font-family:inherit;
    font-size:12px;
    box-shadow:0 1px 1px rgba(15,23,42,0.04);
    transition:border-color .15s ease,box-shadow .15s ease,background .15s ease;
  }
  .field::placeholder{
    color:var(--text-soft);
  }
  .field:focus-visible,
  .field:focus{
    outline:none;
    border-color:var(--accent-red);
    background:#fef2f2;
    box-shadow:0 0 0 1px rgba(185,28,28,0.35);
  }

  /* Buttons */
  .btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:.5rem;
    border-radius:999px;
    padding:7px 14px;
    font-weight:600;
    font-size:11px;
    line-height:1.2;
    cursor:pointer;
    border:1px solid rgba(15,23,42,0.45);
    background:#f9fafb;
    color:var(--text-main);
    box-shadow:0 2px 6px rgba(15,23,42,0.12);
    text-transform:none;
    text-decoration:none;
    transition:background .12s ease,transform .12s ease,box-shadow .12s ease;
  }
  .btn:hover{
    background:#e5e7eb;
    transform:translateY(-1px);
    box-shadow:0 4px 10px rgba(15,23,42,0.18);
  }
  .btn:active{
    transform:translateY(0);
    box-shadow:0 1px 3px rgba(15,23,42,0.2);
  }

  .btn-primary{
    background:var(--accent-red);
    color:#fef2f2;
    border-color:var(--accent-red);
    box-shadow:0 3px 10px rgba(185,28,28,0.4);
  }
  .btn-primary:hover{
    background:#991b1b;
  }

  .btn-danger{
    background:#fee2e2;
    color:#991b1b;
    border-color:#fecaca;
    box-shadow:0 3px 10px rgba(239,68,68,0.25);
  }
  .btn-danger:hover{
    background:#fecaca;
  }

  /* Alerts */
  .alert{
    border-radius:10px;
    padding:.55rem .8rem;
    font-size:.8rem;
    margin-bottom:.75rem;
    border:1px solid rgba(15,23,42,0.16);
    box-shadow:0 2px 8px rgba(15,23,42,0.05);
    background:#ffffff;
  }
  .alert-success{
    background:#ecfdf3;
    color:#166534;
    border-color:rgba(34,197,94,0.35);
  }
  .alert-error{
    background:#fef2f2;
    color:#7f1d1d;
    border-color:rgba(239,68,68,0.4);
  }

  @media (max-width:768px){
    .glass-wrap{
      padding:1.25rem 1rem;
    }
    .glass-card{
      padding:1.25rem 1.1rem;
    }
  }
</style>
@endsection

@section('content')
<div class="p-6">
  <div class="max-w-3xl mx-auto glass-wrap">
    <div class="glass-card">

      <div class="flex items-center justify-between mb-4 gap-3">
        <h2>Edit Employee</h2>
        <span class="id-tag">
          <span>ID</span>
          <span>EMP{{ str_pad($employee->id, 4, '0', STR_PAD_LEFT) }}</span>
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
            <label class="text-xs text-gray-700 mb-1 block">First name</label>
            <input name="first_name"
                   class="field w-full"
                   value="{{ old('first_name',$employee->first_name) }}"
                   required>
            @error('first_name')
              <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
            @enderror
          </div>

          <div>
            <label class="text-xs text-gray-700 mb-1 block">Last name</label>
            <input name="last_name"
                   class="field w-full"
                   value="{{ old('last_name',$employee->last_name) }}"
                   required>
            @error('last_name')
              <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
            @enderror
          </div>

          <div>
            <label class="text-xs text-gray-700 mb-1 block">Email</label>
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
            <label class="text-xs text-gray-700 mb-1 block">Username</label>
            <input name="username"
                   class="field w-full"
                   value="{{ old('username',$employee->username) }}"
                   required>
            @error('username')
              <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
            @enderror
          </div>

          <div>
            <label class="text-xs text-gray-700 mb-1 block">Position</label>
            <input name="position"
                   class="field w-full"
                   value="{{ old('position',$employee->position) }}"
                   placeholder="e.g., Production Supervisor">
            @error('position')
              <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
            @enderror
          </div>

          <div>
            <label class="text-xs text-gray-700 mb-1 block">Status</label>
            <select name="status" class="field w-full">
              <option value="active"   @selected(old('status',$employee->status)==='active')>Active</option>
              <option value="inactive" @selected(old('status',$employee->status)==='inactive')>Inactive</option>
            </select>
            @error('status')
              <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
            @enderror
          </div>

          <div>
            <label class="text-xs text-gray-700 mb-1 block">Avatar</label>
            <input type="file"
                   name="avatar"
                   accept="image/*"
                   class="field w-full">
            @error('avatar')
              <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
            @enderror

            @if($employee->avatar_url)
              <label class="inline-flex items-center gap-1 mt-2 text-xs text-gray-700">
                <input type="checkbox" name="remove_avatar" value="1">
                Remove current avatar
              </label>
            @endif
          </div>
        </div>

        @if($employee->avatar_url)
          <div class="pt-1">
            <div class="text-xs text-gray-700 mb-1">Current avatar</div>
            <img src="{{ $employee->avatar_url }}"
                 class="h-16 w-16 object-cover rounded-full"
                 style="border:2px solid rgba(220,38,38,0.5); box-shadow:0 0 0 3px #fee2e2;"
                 alt="Avatar">
          </div>
        @endif

        <div class="flex items-center justify-between pt-4 gap-3">
          <a href="{{ route('employees.show', $employee->id) }}" class="btn">
            ← Cancel
          </a>

          <button type="submit" class="btn btn-primary">
            Save changes
          </button>
        </div>
      </form>

      {{-- DELETE FORM --}}
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
</div>
@endsection
