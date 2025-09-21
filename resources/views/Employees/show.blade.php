{{-- resources/views/employees/show.blade.php --}}
@extends('layout.mainlayout')

@section('head')
<style>
  .glass-card {
    background: linear-gradient(180deg, rgba(255, 255, 255, .95), rgba(245, 245, 245, .9));
    backdrop-filter: blur(12px);
    border: 1px solid rgba(0, 0, 0, .1);
    border-radius: 1rem;
    padding: 1.5rem;
    color: #111;
  }
  .status-pill {
    padding: .25rem .75rem;
    border-radius: .75rem;
    font-size: .85rem;
    font-weight: 600;
  }
</style>
@endsection

@section('content')
<div class="p-6">
  <div class="glass-card max-w-2xl mx-auto">
    <h2 class="text-xl font-bold mb-4">Employee Details</h2>

    <div class="flex items-center gap-4 mb-6">
      <div class="h-16 w-16 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center text-lg font-semibold text-[#1F4B2C]">
        @if(!empty($employee->avatar_url))
          <img src="{{ $employee->avatar_url }}" class="h-full w-full object-cover" alt="{{ $employee->first_name }}">
        @else
          {{ strtoupper(substr($employee->first_name,0,1).substr($employee->last_name,0,1)) }}
        @endif
      </div>
      <div>
        <h3 class="text-lg font-semibold">{{ $employee->first_name }} {{ $employee->last_name }}</h3>
        <p class="text-sm text-gray-600">{{ $employee->position ?: '—' }}</p>
        <span class="status-pill {{ $employee->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-300 text-gray-700' }}">
          {{ ucfirst($employee->status) }}
        </span>
      </div>
    </div>

    <div class="space-y-3 text-sm text-gray-700">
      <p><strong>Email:</strong> {{ $employee->email ?? '—' }}</p>
      <p><strong>Username:</strong> {{ $employee->username ?? '—' }}</p>
      <p><strong>Position:</strong> {{ $employee->position ?? '—' }}</p>
      <p><strong>Status:</strong> {{ ucfirst($employee->status) }}</p>
      <p><strong>Employee ID:</strong> EMP{{ str_pad($employee->id, 4, '0', STR_PAD_LEFT) }}</p>
      <p><strong>Created At:</strong> {{ $employee->created_at?->format('M d, Y h:i A') }}</p>
      <p><strong>Updated At:</strong> {{ $employee->updated_at?->format('M d, Y h:i A') }}</p>
    </div>

    <div class="mt-6 flex justify-between">
      <a href="{{ route('employees.index') }}" class="btn">← Back</a>
      <a href="{{ route('employees.edit', $employee->id) }}" class="btn-dark">Edit</a>
    </div>
  </div>
</div>
@endsection
