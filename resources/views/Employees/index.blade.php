@extends('layout.mainlayout')

@section('head')
<style>
  /* Glass effect background */
  .glass-wrap {
    background:
      radial-gradient(1200px 800px at 70% -10%, rgba(255, 255, 255, .65), rgba(255, 255, 255, .25)),
      linear-gradient(180deg, rgba(255, 255, 255, .4), rgba(255, 255, 255, .2));
    color: #1a1a1a; /* default dark text */
  }

  .glass-card {
    background: linear-gradient(180deg, rgba(255, 255, 255, .95), rgba(245, 245, 245, .9));
    backdrop-filter: blur(12px);
    border: 1px solid rgba(0, 0, 0, .1);
    color: #111; /* readable text */
  }
  .glass-card:hover {
    box-shadow: 0 10px 30px rgba(18, 18, 18, .12);
  }

  /* Text improvements */
  h2, h3, label {
    color: #111 !important;
  }
  p, .text-xs, .text-sm {
    color: #333 !important;
  }

  .status-pill {
    padding: .125rem .55rem;
    border-radius: .625rem;
    font-size: .75rem;
    line-height: 1;
    font-weight: 600;
  }

  .tab {
    transition: all .15s ease;
    color: #222;
  }
  .tab-active {
    background: #ffffff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
    font-weight: 600;
    color: #111;
  }

  .btn {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    border: 1px solid rgba(0, 0, 0, .15);
    border-radius: .75rem;
    padding: .5rem .75rem;
    background: #f9f9f9;
    color: #111;
    font-weight: 500;
  }
  .btn:hover { background: #f1f1f1; }

  .btn-dark {
    background: #111;
    color: #fff;
    border-color: #111;
  }
  .btn-dark:hover { opacity: .9; }

  .field {
    border: 1px solid rgba(0, 0, 0, .15);
    border-radius: .75rem;
    padding: .5rem .75rem;
    background: #fff;
    color: #111;
  }

  /* Modal backdrop */
  .modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(0, 0, 0, .5);
    display: none;
  }
  .modal {
    position: fixed; inset: 0;
    display: none;
    place-items: center;
    padding: 1rem;
  }
  .modal.open, .modal-backdrop.open { display: grid; }
</style>
@endsection

@section('content')
<div class="p-6 glass-wrap rounded-2xl">

  {{-- Header --}}
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div class="space-y-1">
      <h2 class="text-xl font-extrabold">Employees</h2>
      @php
        $k = $kpis ?? ['all' => ($employees instanceof \Illuminate\Pagination\AbstractPaginator ? $employees->total() : $employees->count()),
                       'active' => null, 'inactive' => null];
      @endphp
      <div class="text-xs text-gray-700">
        All ({{ $k['all'] ?? '—' }})
        @if(!is_null($k['active'])) · Active ({{ $k['active'] }}) @endif
        @if(!is_null($k['inactive'])) · Inactive ({{ $k['inactive'] }}) @endif
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
      <button type="button" class="btn" id="openAddModal">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <path stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M12 5v14M5 12h14"/>
        </svg>
        Add Employee
      </button>

      <form method="GET" action="{{ route('employees.index') }}">
        <input type="hidden" name="status" value="{{ request('status','all') }}">
        <input type="hidden" name="search" value="{{ request('search') }}">
        <input type="hidden" name="sort" value="{{ request('sort','first_asc') }}">
        <input type="hidden" name="per_page" value="{{ request('per_page',12) }}">
        <input type="hidden" name="export" value="csv">
        <button class="btn" type="submit">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path stroke="currentColor" stroke-width="1.6" d="M12 3v12m0 0-4-4m4 4 4-4M4 17h16"/>
          </svg>
          Export CSV
        </button>
      </form>
    </div>
  </div>

  {{-- Filters row --}}
  <form method="GET" action="{{ route('employees.index') }}" id="filtersForm" class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex items-center gap-3">
      @php $currentStatus = request('status','all'); @endphp
      <div class="flex gap-2 rounded-full bg-white/70 px-1 py-1 backdrop-blur">
        @foreach (['all'=>'All','active'=>'Active','inactive'=>'Inactive'] as $key=>$label)
          <button name="status" value="{{ $key }}"
                  class="tab px-3 py-1 rounded-full text-sm {{ $currentStatus===$key ? 'tab-active' : 'hover:bg-gray-200' }}">
            {{ $label }}
          </button>
        @endforeach
      </div>

      @php $sort = request('sort','first_asc'); @endphp
      <select name="sort" class="field text-sm" onchange="this.form.submit()">
        <option value="first_asc"  @selected($sort==='first_asc')>First name ↑</option>
        <option value="first_desc" @selected($sort==='first_desc')>First name ↓</option>
        <option value="last_asc"   @selected($sort==='last_asc')>Last name ↑</option>
        <option value="last_desc"  @selected($sort==='last_desc')>Last name ↓</option>
        <option value="newest"     @selected($sort==='newest')>Newest</option>
        <option value="oldest"     @selected($sort==='oldest')>Oldest</option>
      </select>

      @php $pp = request('per_page',12); @endphp
      <select name="per_page" class="field text-sm" onchange="this.form.submit()">
        @foreach([12,24,36,48,'all'] as $opt)
          <option value="{{ $opt }}" @selected((string)$pp===(string)$opt)>Per page: {{ is_numeric($opt) ? $opt : 'All' }}</option>
        @endforeach
      </select>
    </div>

    <div class="relative w-full lg:w-96">
      <input type="text" name="search" value="{{ request('search') }}"
             placeholder="Search name, email, position"
             class="w-full field pl-10" id="searchInput" />
      <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24"
           stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
           d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/></svg>
    </div>
  </form>

  {{-- Employee cards --}}
  <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3">
    @forelse ($employees as $emp)
      <div class="glass-card rounded-2xl p-4 transition">
        <div class="flex items-start gap-4">
          <div class="h-12 w-12 shrink-0 overflow-hidden rounded-full ring-1 ring-black/10 bg-gray-100">
            @if(!empty($emp->avatar_url))
              <img src="{{ $emp->avatar_url }}" alt="{{ $emp->first_name }}" class="h-full w-full object-cover">
            @else
              <div class="h-full w-full grid place-items-center text-sm font-semibold text-[#1F4B2C]">
                {{ strtoupper(substr($emp->first_name,0,1).substr($emp->last_name,0,1)) }}
              </div>
            @endif
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
              <h3 class="truncate font-semibold">{{ $emp->first_name }} {{ $emp->last_name }}</h3>
              <span class="status-pill {{ $emp->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-300 text-gray-700' }}">
                {{ ucfirst($emp->status) }}
              </span>
            </div>
            <p class="text-xs text-gray-600 leading-5">{{ $emp->position ?: '—' }}</p>
            <p class="mt-1 text-xs text-gray-600">
              Email: <span class="break-all">{{ $emp->email ?? $emp->username }}</span>
            </p>
          </div>
        </div>

        <div class="mt-4 flex items-center justify-between">
          <form method="POST" action="{{ route('employees.toggle-block', $emp->id) }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn"> {{ $emp->status === 'active' ? 'Block' : 'Unblock' }} </button>
          </form>

          <a href="{{ route('employees.show', $emp->id) }}" class="btn-dark rounded-lg px-3 py-1 text-sm">
            Details
          </a>
        </div>

        <div class="mt-3 text-[11px] text-gray-500">ID: EMP{{ str_pad($emp->id, 4, '0', STR_PAD_LEFT) }}</div>
      </div>
    @empty
      <div class="col-span-full">
        <div class="glass-card rounded-2xl p-10 text-center">
          <h3 class="text-base font-semibold">No employees found</h3>
          <p class="mt-1 text-sm text-gray-600">Try clearing filters or add a new employee.</p>
          <button type="button" id="openAddModalEmpty" class="mt-4 btn">Add Employee</button>
        </div>
      </div>
    @endforelse
  </div>

  @if(method_exists($employees, 'links'))
    <div class="mt-6">
      {{ $employees->appends(request()->only('status','search','sort','per_page'))->links() }}
    </div>
  @endif
</div>

{{-- Modal --}}
<div class="modal-backdrop" id="modalBackdrop"></div>
<div class="modal" id="addModal" aria-modal="true" role="dialog">
  <div class="glass-card w-full max-w-xl rounded-2xl p-6 relative">
    <button type="button" class="absolute right-3 top-3 text-black/60 hover:text-black" id="closeAddModal">✕</button>
    <h3 class="text-lg font-semibold mb-4">Add Employee</h3>

    <form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data" class="space-y-3">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-gray-600">First name</label>
          <input name="first_name" class="field w-full" required>
        </div>
        <div>
          <label class="text-xs text-gray-600">Last name</label>
          <input name="last_name" class="field w-full" required>
        </div>
        <div>
          <label class="text-xs text-gray-600">Email</label>
          <input type="email" name="email" class="field w-full" required>
        </div>
        <div>
          <label class="text-xs text-gray-600">Username</label>
          <input name="username" class="field w-full" required>
        </div>
        <div>
          <label class="text-xs text-gray-600">Position</label>
          <input name="position" class="field w-full" placeholder="e.g., Production Supervisor">
        </div>
        <div>
          <label class="text-xs text-gray-600">Status</label>
          <select name="status" class="field w-full">
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-600">Password</label>
          <input type="password" name="password" class="field w-full" required minlength="6">
        </div>
        <div>
          <label class="text-xs text-gray-600">Avatar (optional)</label>
          <input type="file" name="avatar" accept="image/*" class="field w-full">
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button" class="btn" id="closeAddModal2">Cancel</button>
        <button type="submit" class="btn-dark rounded-xl px-4 py-2">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Debounced search
  (function(){
    const input = document.getElementById('searchInput');
    const form  = document.getElementById('filtersForm');
    if(!input || !form) return;
    let t; input.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(() => form.submit(), 350);
    });
  })();

  // Modal open/close
  const addModal = document.getElementById('addModal');
  const backdrop = document.getElementById('modalBackdrop');
  const openBtns = [document.getElementById('openAddModal'), document.getElementById('openAddModalEmpty')].filter(Boolean);
  const closeBtns = [document.getElementById('closeAddModal'), document.getElementById('closeAddModal2')];

  function openModal(){
    addModal.classList.add('open'); backdrop.classList.add('open');
  }
  function closeModal(){
    addModal.classList.remove('open'); backdrop.classList.remove('open');
  }
  openBtns.forEach(btn => btn && btn.addEventListener('click', openModal));
  closeBtns.forEach(btn => btn && btn.addEventListener('click', closeModal));
  backdrop.addEventListener('click', closeModal);
  window.addEventListener('keydown', e => { if(e.key === 'Escape') closeModal(); });
</script>
@endsection
