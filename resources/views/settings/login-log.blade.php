@extends('layout.mainlayout')

@section('title', 'Login Log Book · GenRev')

@section('head')
<style>
  .log-shell{
    max-width:1050px;
    margin:1.75rem auto;
  }
  .log-card{
    background:linear-gradient(180deg,rgba(255,255,255,.97),rgba(248,250,252,.96));
    border-radius:1.25rem;
    border:1px solid rgba(148,163,184,.35);
    box-shadow:0 18px 45px rgba(15,23,42,.12);
    padding:1.5rem 1.75rem;
  }
  .log-header-title{
    font-size:1.1rem;
    font-weight:700;
    color:#0f172a;
  }
  .log-subtitle{
    font-size:.8rem;
    color:#64748b;
  }
  .soft-input{
    border-radius:.8rem;
    border:1px solid #e2e8f0;
    padding:.45rem .7rem;
    font-size:.8rem;
    background:#f8fafc;
    color:#0f172a;
  }
  .soft-input:focus{
    outline:none;
    border-color:#2563eb;
    box-shadow:0 0 0 1px rgba(37,99,235,.45);
    background:#fff;
  }
  .soft-select{
    border-radius:.8rem;
    border:1px solid #e2e8f0;
    padding:.4rem .7rem;
    font-size:.8rem;
    background:#f8fafc;
    color:#0f172a;
  }
  .soft-pill{
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    font-size:.7rem;
    font-weight:600;
    padding:.25rem .6rem;
    border-radius:999px;
    border:1px solid #e5e7eb;
    background:#f9fafb;
    color:#111827;
  }
  .btn-filter{
    border-radius:.8rem;
    padding:.45rem .9rem;
    font-size:.8rem;
    font-weight:600;
    border:1px solid #e2e8f0;
    background:#0f172a;
    color:#fff;
    cursor:pointer;
    transition:filter .15s ease, transform .1s ease;
  }
  .btn-filter:hover{
    filter:brightness(.96);
    transform:translateY(-1px);
  }
  .table-shell{
    margin-top:1rem;
    border-radius:.85rem;
    overflow:hidden;
    border:1px solid #e2e8f0;
    background:#fff;
  }
  table.login-table{
    width:100%;
    border-collapse:collapse;
    font-size:.8rem;
  }
  .login-table thead{
    background:linear-gradient(90deg,#f9fafb,#f1f5f9);
  }
  .login-table th,
  .login-table td{
    padding:.65rem .75rem;
    text-align:left;
  }
  .login-table th{
    font-weight:600;
    color:#475569;
  }
  .login-table tbody tr:nth-child(even){
    background:#f9fafb;
  }
  .login-table tbody tr:hover{
    background:#eff6ff;
  }
  .user-name{
    font-weight:600;
    color:#0f172a;
  }
  .user-email{
    font-size:.74rem;
    color:#64748b;
  }
  .status-pill-success{
    background:#dcfce7;
    color:#166534;
    border-radius:999px;
    padding:.18rem .6rem;
    font-size:.72rem;
    font-weight:600;
    display:inline-flex;
    align-items:center;
    gap:.25rem;
  }
  .status-pill-failed{
    background:#fee2e2;
    color:#b91c1c;
    border-radius:999px;
    padding:.18rem .6rem;
    font-size:.72rem;
    font-weight:600;
    display:inline-flex;
    align-items:center;
    gap:.25rem;
  }
  .badge-dot{
    width:.4rem;
    height:.4rem;
    border-radius:999px;
  }
</style>
@endsection

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
  <div class="log-shell">
    <div class="log-card">
      {{-- Header --}}
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
          <div class="log-header-title">Login Log Book</div>
          <div class="log-subtitle">Audit trail of login attempts across all users.</div>
        </div>

        {{-- Filter bar --}}
        <form method="GET" action="{{ route('settings.login-activity') }}" class="flex flex-wrap items-center gap-2">
          {{-- Status select --}}
          <select name="status" class="soft-select">
            <option value="" {{ $status === null || $status === '' ? 'selected' : '' }}>Status: All</option>
            <option value="success" {{ $status === 'success' ? 'selected' : '' }}>Success only</option>
            <option value="failed"  {{ $status === 'failed' ? 'selected' : '' }}>Failed only</option>
          </select>

          {{-- Search --}}
          <input
            type="text"
            name="search"
            value="{{ $search }}"
            placeholder="Search by name or email"
            class="soft-input"
          >

          <button type="submit" class="btn-filter">
            Filter
          </button>
        </form>
      </div>

      {{-- Table --}}
      <div class="table-shell">
        <div class="overflow-x-auto">
          <table class="login-table">
            <thead>
              <tr>
                <th>When</th>
                <th>User</th>
                <th>Role</th>
                <th>IP</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>
              @forelse ($activities as $log)
                <tr>
                  {{-- When: date only --}}
                  <td>
                    {{ optional($log->login_at)->format('M d, Y') ?: '—' }}
                  </td>

                  {{-- User --}}
                  <td>
                    @if ($log->user)
                      <div class="user-name">{{ $log->user->name }}</div>
                      <div class="user-email">{{ $log->user->email }}</div>
                    @else
                      <span class="text-gray-400 italic">Unknown user</span>
                    @endif
                  </td>

                  {{-- Role (from linked user) --}}
                  <td>
                    @if ($log->user && $log->user->role)
                      <span class="soft-pill">
                        {{ ucwords($log->user->role) }}
                      </span>
                    @else
                      <span class="text-gray-400">—</span>
                    @endif
                  </td>

                  {{-- IP --}}
                  <td>
                    {{ $log->ip_address ?: '—' }}
                  </td>

                  {{-- Time In (login_at time) --}}
                  <td>
                    {{ optional($log->login_at)->format('H:i:s') ?: '—' }}
                  </td>

                  {{-- Time Out (logout_at time) --}}
                  <td>
                    {{ optional($log->logout_at)->format('H:i:s') ?: '—' }}
                  </td>

                  {{-- Status --}}
                  <td>
                    @if ($log->succeeded)
                      <span class="status-pill-success">
                        <span class="badge-dot" style="background:#16a34a"></span>
                        Success
                      </span>
                    @else
                      <span class="status-pill-failed">
                        <span class="badge-dot" style="background:#dc2626"></span>
                        Failed
                      </span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="py-4 text-center text-gray-500">
                    No login activity recorded yet.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- Pagination (keeps filters & search) --}}
      <div class="mt-4">
        {{ $activities->appends(request()->query())->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
