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
    --accent-yellow:#facc15;
    --accent-green:#16a34a;
    --accent-green-soft:#ecfdf3;
    --accent-amber:#fbbf24;
    --text-main:#111827;
    --text-muted:#6b7280;
    --text-soft:#9ca3af;
  }

  body{
    font-family:system-ui,-apple-system,BlinkMacSystemFont,"SF Pro Text","Segoe UI",sans-serif;
  }

  /* === PAGE WRAPPER / PANEL === */
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
    box-shadow:0 10px 25px rgba(15,23,42,0.05);
    color:var(--text-main);
    transition:box-shadow .15s ease,transform .15s ease;
  }
  .glass-card:hover{
    box-shadow:0 16px 40px rgba(15,23,42,0.14);
    transform:translateY(-2px);
  }

  h2{
    color:var(--text-main) !important;
    font-size:20px;
    font-weight:800;
    letter-spacing:.03em;
  }
  h3,label{
    color:var(--text-main) !important;
  }
  p,.text-xs,.text-sm{
    color:var(--text-muted) !important;
  }

  /* === KPI STRIPS (HEAD COUNTS) === */
  .pixel-strip-row{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
  }
  .pixel-strip{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:8px 12px;
    border-radius:999px;
    border:1px solid rgba(15,23,42,0.15);
    background:#f9fafb;
    min-width:160px;
  }
  .pixel-strip-label{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:11px;
    color:var(--text-muted);
    font-weight:600;
  }
  .pixel-strip-icon{
    width:16px;
    height:16px;
    border-radius:999px;
    background:#fee2e2;
  }
  .pixel-strip--yellow .pixel-strip-icon{
    background:#fef3c7;
  }
  .pixel-strip--green .pixel-strip-icon{
    background:#bbf7d0;
  }
  .pixel-strip--red .pixel-strip-icon{
    background:#fecaca;
  }
  .pixel-strip--yellow{
    background:#fffbeb;
  }
  .pixel-strip--green{
    background:#ecfdf3;
  }
  .pixel-strip--red{
    background:#fef2f2;
  }
  .pixel-strip-count{
    min-width:34px;
    text-align:center;
    padding:3px 8px;
    font-size:12px;
    font-weight:700;
    border-radius:999px;
    background:#ffffff;
    color:var(--accent-red);
    border:1px solid rgba(15,23,42,0.12);
  }

  /* === STATUS PILL === */
  .status-pill{
    padding:3px 8px;
    font-size:10px;
    line-height:1.3;
    font-weight:600;
    border-radius:999px;
    border:1px solid rgba(15,23,42,0.2);
    background:#f3f4f6;
  }

  /* === TABS (All / Active / Inactive) === */
  .tab{
    border-radius:999px;
    border:1px solid rgba(15,23,42,0.15);
    background:#f3f4f6;
    font-size:11px;
    font-weight:600;
    padding:6px 14px;
    cursor:pointer;
    color:var(--text-main);
    transition:background .12s ease,box-shadow .12s ease,transform .12s ease;
  }
  .tab:hover:not(.tab-active){
    background:#e5e7eb;
    box-shadow:0 1px 4px rgba(15,23,42,0.12);
    transform:translateY(-1px);
  }
  .tab-active{
    color:#111827;
    box-shadow:0 2px 6px rgba(15,23,42,0.16);
  }
  .tab-all.tab-active{
    background:#fef3c7;
    border-color:#facc15;
  }
  .tab-active-status.tab-active{
    background:#ecfdf3;
    border-color:#16a34a;
  }
  .tab-inactive-status.tab-active{
    background:#fee2e2;
    border-color:#f97316;
  }

  /* === INPUTS / SELECTS === */
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

  /* === BUTTONS === */
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
  }
  .btn svg{
    flex-shrink:0;
    width:14px;
    height:14px;
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

  .btn-dark{
    border-radius:999px;
    border:1px solid rgba(31,41,55,0.9);
    background:#374151;
    color:#f9fafb;
    box-shadow:0 3px 10px rgba(15,23,42,0.35);
    font-size:11px;
    text-transform:none;
  }
  .btn-dark:hover{
    background:#4b5563;
  }
  .btn-dark:active{
    box-shadow:0 1px 4px rgba(15,23,42,0.4);
  }

  .btn-detail{
    border-radius:999px;
    border:1px solid rgba(250,204,21,0.8);
    background:#fef3c7;
    color:#854d0e;
    box-shadow:0 2px 8px rgba(250,204,21,0.35);
    font-size:11px;
    text-transform:none;
  }
  .btn-detail:hover{
    background:#fde68a;
  }
  .btn-detail:active{
    box-shadow:0 1px 4px rgba(250,204,21,0.45);
  }

  /* === MODAL === */
  .modal-backdrop{
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.55);
    display:none;
    z-index:40;
    -webkit-backdrop-filter:blur(3px);
    backdrop-filter:blur(3px);
  }
  .modal{
    position:fixed;
    inset:0;
    display:none;
    align-items:center;
    justify-content:center;
    padding:1rem;
    z-index:50;
  }
  .modal.open,
  .modal-backdrop.open{
    display:flex;
  }

  @media (max-width:768px){
    .glass-wrap{
      padding:1.25rem 1rem;
    }
  }
</style>
@endsection

@section('content')
<div class="p-6 glass-wrap">

  {{-- Header --}}
  <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
    <div class="space-y-2 w-full md:w-auto">
      <h2 class="font-extrabold">Employees · GenRev Meat Products</h2>
      @php
        $k = $kpis ?? [
          'all'      => ($employees instanceof \Illuminate\Pagination\AbstractPaginator ? $employees->total() : $employees->count()),
          'active'   => null,
          'inactive' => null
        ];
      @endphp

      {{-- KPI strips --}}
      <div class="pixel-strip-row">
        <div class="pixel-strip pixel-strip--yellow">
          <div class="pixel-strip-label">
            <span class="pixel-strip-icon"></span>
            <span>All Employees</span>
          </div>
          <span class="pixel-strip-count">{{ $k['all'] ?? 0 }}</span>
        </div>

        @if(!is_null($k['active']))
          <div class="pixel-strip pixel-strip--green">
            <div class="pixel-strip-label">
              <span class="pixel-strip-icon"></span>
              <span>Active</span>
            </div>
            <span class="pixel-strip-count">{{ $k['active'] }}</span>
          </div>
        @endif

        @if(!is_null($k['inactive']))
          <div class="pixel-strip pixel-strip--red">
            <div class="pixel-strip-label">
              <span class="pixel-strip-icon"></span>
              <span>Inactive</span>
            </div>
            <span class="pixel-strip-count">{{ $k['inactive'] }}</span>
          </div>
        @endif
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 md:pt-2">
      <button type="button" class="btn" id="openAddModal">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <path stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M12 5v14M5 12h14"/>
        </svg>
        Add Employee
      </button>
    </div>
  </div>

  {{-- Filters row --}}
  <form method="GET" action="{{ route('employees.index') }}" id="filtersForm" class="mt-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex items-center gap-3 flex-wrap">
      @php $currentStatus = request('status','all'); @endphp
      <div class="flex gap-2">
        <button name="status" value="all"
                class="tab tab-all {{ $currentStatus==='all' ? 'tab-active' : '' }}">
          All
        </button>
        <button name="status" value="active"
                class="tab tab-active-status {{ $currentStatus==='active' ? 'tab-active' : '' }}">
          Active
        </button>
        <button name="status" value="inactive"
                class="tab tab-inactive-status {{ $currentStatus==='inactive' ? 'tab-active' : '' }}">
          Inactive
        </button>
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
          <option value="{{ $opt }}" @selected((string)$pp===(string)$opt)>
            Per page: {{ is_numeric($opt) ? $opt : 'All' }}
          </option>
        @endforeach
      </select>
    </div>

    <div class="relative w-full lg:w-96">
      <input type="text" name="search" value="{{ request('search') }}"
             placeholder="Search name, email, position"
             class="w-full field pl-10" id="searchInput" />
      <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24"
           stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
      </svg>
    </div>
  </form>

  {{-- Employee cards --}}
  <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3">
    @forelse ($employees as $emp)
      @php
        $roleLabel = $emp->position ?: ($emp->user->role ?? null);
      @endphp

      <div class="glass-card p-4">
        <div class="flex items-start gap-4">
          <div class="h-12 w-12 shrink-0 overflow-hidden bg-gray-100 rounded-full ring-2 ring-red-100">
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
              <h3 class="truncate font-semibold text-[13px]">
                {{ $emp->first_name }} {{ $emp->last_name }}
              </h3>
              <span class="status-pill {{ $emp->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-800' }}">
                {{ ucfirst($emp->status) }}
              </span>
            </div>

            <p class="text-xs leading-5 mt-1">
              {{ $roleLabel ? ucwords($roleLabel) : '—' }}
            </p>

            <p class="mt-1 text-xs">
              Email: <span class="break-all text-gray-700">{{ $emp->email ?? $emp->username }}</span>
            </p>
          </div>
        </div>

        <div class="mt-4 flex items-center justify-between">
          <form method="POST" action="{{ route('employees.toggle-block', $emp->id) }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn">
              {{ $emp->status === 'active' ? 'Block' : 'Unblock' }}
            </button>
          </form>

          <a href="{{ route('employees.show', $emp->id) }}" class="btn-detail px-3 py-1">
            Details
          </a>
        </div>

        <div class="mt-3 text-[10px] text-gray-500">
          ID: EMP{{ str_pad($emp->id, 4, '0', STR_PAD_LEFT) }}
        </div>
      </div>
    @empty
      <div class="col-span-full">
        <div class="glass-card p-10 text-center">
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
  <div class="glass-card w-full max-w-xl p-6 relative">
    <button type="button" class="absolute right-3 top-3 text-black/60 hover:text-black" id="closeAddModal">✕</button>
    <h3 class="text-lg font-semibold mb-4 text-red-800">Add Employee</h3>

    <form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data" class="space-y-3">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-gray-600 mb-1 block">First name</label>
          <input name="first_name" class="field w-full" required>
        </div>
        <div>
          <label class="text-xs text-gray-600 mb-1 block">Last name</label>
          <input name="last_name" class="field w-full" required>
        </div>
        <div>
          <label class="text-xs text-gray-600 mb-1 block">Email</label>
          <input type="email" name="email" class="field w-full" required>
        </div>
        <div>
          <label class="text-xs text-gray-600 mb-1 block">Username</label>
          <input name="username" class="field w-full" required>
        </div>
        <div>
          <label class="text-xs text-gray-600 mb-1 block">Position</label>
          <input name="position" class="field w-full" placeholder="e.g., Production Supervisor">
        </div>
        <div>
          <label class="text-xs text-gray-600 mb-1 block">Status</label>
          <select name="status" class="field w-full">
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-600 mb-1 block">Password</label>
          <input type="password" name="password" class="field w-full" required minlength="6">
        </div>
        <div>
          <label class="text-xs text-gray-600 mb-1 block">Avatar (optional)</label>
          <input type="file" name="avatar" accept="image/*" class="field w-full">
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button" class="btn" id="closeAddModal2">Cancel</button>
        <button type="submit" class="btn-dark px-4 py-2">Save</button>
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
