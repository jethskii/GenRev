{{-- resources/views/employees/show.blade.php --}}
@extends('layout.mainlayout')

@section('head')
<style>
  :root{
    --bg-body:#f5f5f6;
    --panel-bg:#fdfdfc;
    --border-soft:rgba(15,23,42,0.16);
    --shadow-main:0 10px 30px rgba(15,23,42,0.12);
    --accent-red:#b91c1c;
    --accent-red-soft:#fee2e2;
    --accent-green:#16a34a;
    --text-main:#111827;
    --text-muted:#6b7280;
    --text-soft:#9ca3af;
  }

  body{
    font-family:system-ui,-apple-system,BlinkMacSystemFont,"SF Pro Text","Segoe UI",sans-serif;
  }

  .glass-wrap{
    background:var(--panel-bg);
    border-radius:16px;
    border:1px solid var(--border-soft);
    box-shadow:var(--shadow-main);
    padding:1.5rem 1.5rem 1.6rem;
    color:var(--text-main);
  }

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
  h3,label,strong{
    color:var(--text-main) !important;
  }
  p,.text-xs,.text-sm{
    color:var(--text-muted) !important;
  }

  .status-pill{
    padding:3px 10px;
    font-size:11px;
    line-height:1.2;
    font-weight:600;
    border-radius:999px;
    border:1px solid rgba(15,23,42,0.15);
    background:#f3f4f6;
  }

  /* Avatar frame */
  .pixel-avatar{
    height:64px;
    width:64px;
    border-radius:999px;
    border:2px solid rgba(220,38,38,0.4);
    box-shadow:0 0 0 3px #fee2e2;
    background:#f9fafb;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    font-size:16px;
  }

  /* Buttons */
  .btn{
    display:inline-flex;
    align-items:center;
    gap:.4rem;
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
    transition:background .12s ease,transform .12s ease,box-shadow .12s ease;
    text-decoration:none;
  }
  .btn:hover{
    background:#e5e7eb;
    transform:translateY(-1px);
    box-shadow:0 4px 10px rgba(15,23,42,0.18);
  }
  .btn:active{
    transform:translateY(0);
    box-shadow:0 1px 3px rgba(15,23,42,0.18);
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
  <div class="max-w-2xl mx-auto glass-wrap">
    <div class="glass-card">
      <h2 class="mb-4">Employee Details</h2>

      <div class="flex items-center gap-4 mb-6">
        <div class="pixel-avatar text-[#1F4B2C]">
          @if(!empty($employee->avatar_url))
            <img src="{{ $employee->avatar_url }}" class="h-full w-full object-cover" alt="{{ $employee->first_name }}">
          @else
            {{ strtoupper(substr($employee->first_name,0,1).substr($employee->last_name,0,1)) }}
          @endif
        </div>
        <div>
          <h3 class="text-base font-semibold">
            {{ $employee->first_name }} {{ $employee->last_name }}
          </h3>
          <p class="text-xs mt-1">
            {{ $employee->position ?: '—' }}
          </p>
          <span class="status-pill mt-2 inline-block {{ $employee->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-800' }}">
            {{ ucfirst($employee->status) }}
          </span>
        </div>
      </div>

      <div class="space-y-3 text-xs">
        <p><strong>Email:</strong> <span class="text-gray-700">{{ $employee->email ?? '—' }}</span></p>
        <p><strong>Username:</strong> <span class="text-gray-700">{{ $employee->username ?? '—' }}</span></p>
        <p><strong>Position:</strong> <span class="text-gray-700">{{ $employee->position ?? '—' }}</span></p>
        <p><strong>Status:</strong> <span class="text-gray-700">{{ ucfirst($employee->status) }}</span></p>
        <p><strong>Employee ID:</strong> <span class="text-gray-700">EMP{{ str_pad($employee->id, 4, '0', STR_PAD_LEFT) }}</span></p>
        <p><strong>Created At:</strong> <span class="text-gray-700">{{ $employee->created_at?->format('M d, Y h:i A') }}</span></p>
        <p><strong>Updated At:</strong> <span class="text-gray-700">{{ $employee->updated_at?->format('M d, Y h:i A') }}</span></p>
      </div>

      <div class="mt-6 flex justify-between gap-3">
        <a href="{{ route('employees.index') }}" class="btn">← Back</a>
        <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-primary">Edit</a>
      </div>
    </div>
  </div>
</div>
@endsection
