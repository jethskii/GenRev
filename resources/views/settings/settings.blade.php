@extends('layout.mainlayout')

@section('head')
<style>
  :root{
    --primary-red:#dc2626; --secondary-blue:#2563eb; --secondary-green:#16a34a; --beige-ink:#0f172a;
  }
  .surface{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:1rem;
    box-shadow:0 1px 1px rgba(0,0,0,.02),0 2px 6px rgba(0,0,0,.04);
    transition:box-shadow .18s ease, transform .12s ease;
  }
  .surface:hover{
    box-shadow:0 2px 4px rgba(0,0,0,.04),0 8px 24px rgba(0,0,0,.08);
    transform:translateY(-1px);
  }
  .soft-pill{
    display:inline-flex;
    align-items:center;
    gap:.5rem;
    padding:.375rem .65rem;
    font-size:.72rem;
    font-weight:600;
    border-radius:999px;
    border:1px solid #e5e7eb;
    background:#fafafa;
    color:#334155;
  }
  .ring-gradient{ position:relative; }
  .ring-gradient::before{
    content:'';
    position:absolute;
    inset:-1px;
    z-index:-1;
    border-radius:1rem;
    background:linear-gradient(120deg, rgba(220,38,38,.35), rgba(22,163,74,.35), rgba(37,99,235,.35));
    opacity:0;
    transition:opacity .25s ease;
  }
  .ring-gradient:hover::before{
    opacity:1;
    filter:saturate(1.2);
  }
  .icon-chip{
    border-radius:.8rem;
    display:inline-grid;
    place-items:center;
    width:40px;
    height:40px;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    font-size:1.1rem;
  }
  .btn{
    border-radius:.65rem;
    font-weight:700;
    font-size:.85rem;
    padding:.55rem .9rem;
    transition:transform .12s ease, filter .15s ease;
    display:inline-flex;
    align-items:center;
    gap:.4rem;
    border:1px solid transparent;
  }
  .btn:hover{ filter:brightness(.97); transform:translateY(-1px); }
  .btn:active{ transform:translateY(0); }
  .btn-red{
    background:var(--primary-red);
    color:#fff;
    border-color:var(--primary-red);
  }
  .btn-blue{
    background:var(--secondary-blue);
    color:#fff;
    border-color:var(--secondary-blue);
  }
  .btn-green{
    background:var(--secondary-green);
    color:#fff;
    border-color:var(--secondary-green);
  }
  .btn-ghost{
    background:#f9fafb;
    color:#111827;
    border-color:#e5e7eb;
  }
  .ribbon{
    background:
      radial-gradient(120% 120% at 0% 100%, rgba(220,38,38,.12), transparent 60%),
      radial-gradient(120% 120% at 100% 0%, rgba(22,163,74,.12), transparent 60%),
      linear-gradient(90deg,#ffffff,#fafafa);
    border:1px solid #e5e7eb;
    border-radius:1rem;
  }
  @media (prefers-reduced-motion:no-preference){
    .fade-in{ animation:fadeIn .25s ease both; }
    @keyframes fadeIn{
      from{opacity:0; transform:translateY(6px)}
      to{opacity:1; transform:none}
    }
  }
</style>
@endsection

@section('content')
<div class="p-6 text-gray-900 space-y-6">

  {{-- Page Header --}}
  <div class="ribbon px-5 py-4 surface">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold">Settings</h1>
        <p class="text-sm text-gray-600">
          Configure your account, preferences, security, and workspace. Primary actions are
          <span class="font-semibold text-red-600">red</span>;
          secondary actions are <span class="font-semibold text-blue-600">blue</span> or
          <span class="font-semibold text-green-600">green</span>.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <span class="soft-pill">
          <span class="w-2 h-2 rounded-full bg-red-500"></span> Primary
        </span>
        <span class="soft-pill">
          <span class="w-2 h-2 rounded-full bg-blue-600"></span> Secondary
        </span>
        <span class="soft-pill">
          <span class="w-2 h-2 rounded-full bg-green-600"></span> Secondary
        </span>
      </div>
    </div>
  </div>

  {{-- Grid Sections --}}
  <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">

    {{-- Account --}}
    <a href="{{ route('settings.account') }}"
       class="surface ring-gradient p-5 rounded-2xl transition fade-in group block cursor-pointer">
      <div class="flex items-start gap-4">
        <div class="icon-chip group-hover:border-gray-300">👤</div>
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <h2 class="text-lg font-semibold">Account Settings</h2>
            <span class="soft-pill">Profile</span>
          </div>
          <p class="text-sm text-gray-600 mt-1">
            Change your name, avatar, bio, and contact details.
          </p>
          <div class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 group-hover:underline">
            Manage account
            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd"
                    d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 11-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z"
                    clip-rule="evenodd" />
            </svg>
          </div>
        </div>
      </div>
    </a>

    {{-- Appearance --}}
    <a href="{{ route('settings.appearance') }}"
       class="surface ring-gradient p-5 rounded-2xl transition fade-in md:col-span-2 xl:col-span-1 group block cursor-pointer">
      <div class="flex items-start gap-4">
        <div class="icon-chip">🎨</div>
        <div class="flex-1">
          <div class="flex items-center gap-2">
            <h2 class="text-lg font-semibold">Appearance</h2>
            <span class="soft-pill">Theme</span>
          </div>
          <p class="text-sm text-gray-600 mt-1">
            Customize theme, density, and dashboard visuals.
          </p>

          <div class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 group-hover:underline">
            Open Appearance
            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd"
                    d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 11-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z"
                    clip-rule="evenodd" />
            </svg>
          </div>
        </div>
      </div>
    </a>

    {{-- 🔎 Login Activity / Log Book --}}
    <a href="{{ route('settings.login-activity') }}"
       class="surface ring-gradient p-5 rounded-2xl transition fade-in group block cursor-pointer">
      <div class="flex items-start gap-4">
        <div class="icon-chip">📜</div>
        <div class="flex-1">
          <div class="flex items-center gap-2 justify-between">
            <div class="flex items-center gap-2">
              <h2 class="text-lg font-semibold">Login Activity</h2>
              <span class="soft-pill">Log book</span>
            </div>
          </div>

          <p class="text-sm text-gray-600 mt-1">
            See who logged in, when, from which IP and device. Useful for audits and security checks.
          </p>

          <div class="mt-4 flex flex-wrap items-center gap-2">
            <span class="text-xs text-gray-500">
              Last 30 days of login attempts (success, failed, locked).
            </span>
            <div class="w-full flex gap-2 pt-1">
              <button type="button" class="btn btn-blue">
                View log
              </button>
              <button type="button" class="btn btn-ghost">
                Export
              </button>
            </div>
          </div>
        </div>
      </div>
    </a>

  </div>
</div>
@endsection
