@extends('layout.mainlayout')

@section('head')
<style>
  :root{
    /* Brand accents (consistent across app) */
    --primary-red: #dc2626;      /* primary actions */
    --secondary-blue: #2563eb;   /* secondary alt */
    --secondary-green: #16a34a;  /* secondary alt */
    --beige-ink: #0f172a;        /* dark ink */
  }

  /* ---------- Fancy but subtle UI polish ---------- */
  .surface {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    box-shadow:
      0 1px 1px rgba(0,0,0,0.02),
      0 2px 6px rgba(0,0,0,0.04);
  }
  .surface:hover { box-shadow:
      0 2px 4px rgba(0,0,0,0.04),
      0 8px 24px rgba(0,0,0,0.08);
  }

  .soft-pill {
    display:inline-flex; align-items:center; gap:.5rem;
    padding:.375rem .65rem; font-size:.72rem; font-weight:600;
    border-radius:999px; border:1px solid #e5e7eb; background:#fafafa; color:#334155;
  }

  /* Gradient border ring on hover */
  .ring-gradient {
    position: relative;
  }
  .ring-gradient::before {
    content:''; position:absolute; inset:-1px; z-index:-1; border-radius:1rem;
    background: linear-gradient(120deg, rgba(220,38,38,.35), rgba(22,163,74,.35), rgba(37,99,235,.35));
    opacity:0; transition:opacity .25s ease;
  }
  .ring-gradient:hover::before { opacity:1; filter:saturate(1.2); }

  /* Icon chip */
  .icon-chip {
    border-radius:.8rem;
    display:inline-grid; place-items:center;
    width:40px; height:40px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
  }

  /* Buttons */
  .btn-red   { background: var(--primary-red);   color:#fff; }
  .btn-blue  { background: var(--secondary-blue);  color:#fff; }
  .btn-green { background: var(--secondary-green); color:#fff; }
  .btn {
    border-radius:.65rem; font-weight:700; font-size:.85rem;
    padding:.55rem .9rem; transition: transform .12s ease, filter .15s ease;
  }
  .btn:hover { filter: brightness(.97); transform: translateY(-1px); }
  .btn:active{ transform: translateY(0); }

  /* Section header ribbon */
  .ribbon {
    background:
      radial-gradient(120% 120% at 0% 100%, rgba(220,38,38,.12), transparent 60%),
      radial-gradient(120% 120% at 100% 0%, rgba(22,163,74,.12), transparent 60%),
      linear-gradient(90deg, #ffffff, #fafafa);
    border: 1px solid #e5e7eb; border-radius: 1rem;
  }

  @media (prefers-reduced-motion:no-preference){
    .fade-in { animation: fadeIn .25s ease both; }
    @keyframes fadeIn { from{opacity:0; transform: translateY(6px)} to{opacity:1; transform: none} }
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
          Configure your account, preferences, and workspace. Primary actions are
          <span class="font-semibold text-red-600">red</span>;
          secondary actions are <span class="font-semibold text-blue-600">blue</span> or
          <span class="font-semibold text-green-600">green</span>.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <span class="soft-pill"><span class="w-2 h-2 rounded-full bg-red-500"></span> Primary</span>
        <span class="soft-pill"><span class="w-2 h-2 rounded-full bg-blue-600"></span> Secondary</span>
        <span class="soft-pill"><span class="w-2 h-2 rounded-full bg-green-600"></span> Secondary</span>
      </div>
    </div>
  </div>

  {{-- Grid Sections --}}
  <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">

    {{-- Account --}}
    <a href="{{ route('settings.account') }}" class="surface ring-gradient p-5 rounded-2xl transition fade-in group">
      <div class="flex items-start gap-4">
        <div class="icon-chip group-hover:border-gray-300">
          👤
        </div>
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <h2 class="text-lg font-semibold">Account Settings</h2>
            <span class="soft-pill">Profile</span>
          </div>
          <p class="text-sm text-gray-600 mt-1">Change your username, photo, bio, and contact details.</p>
          <div class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 group-hover:underline">
            Manage account
            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l5 5a1
              1 0 010 1.414l-5 5a1 1 0 11-1.414-1.414L13.586 10H4a1 1 0
              110-2h9.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
          </div>
        </div>
      </div>
    </a>

    {{-- User Management --}}
    <div class="surface ring-gradient p-5 rounded-2xl transition fade-in">
      <div class="flex items-start gap-4">
        <div class="icon-chip">🧑‍🤝‍🧑</div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <h2 class="text-lg font-semibold">User Management</h2>
            <span class="soft-pill">Admin</span>
          </div>
          <p class="text-sm text-gray-600 mt-1">Manage user accounts and permissions.</p>

          <div class="mt-4 flex flex-wrap items-center gap-2">
            <button class="btn btn-red">Add User</button>
            <button class="btn btn-blue">View Users</button>
            <button class="btn btn-green">Export</button>
          </div>
        </div>
      </div>
    </div>

    {{-- Notifications --}}
    <div class="surface ring-gradient p-5 rounded-2xl transition fade-in">
      <div class="flex items-start gap-4">
        <div class="icon-chip">🔔</div>
        <div class="flex-1">
          <div class="flex items-center gap-2">
            <h2 class="text-lg font-semibold">Notifications</h2>
            <span class="soft-pill">Preferences</span>
          </div>
          <p class="text-sm text-gray-600 mt-1">Set preferences for email and in-app alerts.</p>

          <form class="mt-4 space-y-3">
            <label class="flex items-center justify-between text-sm">
              <span>Email summaries</span>
              <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            </label>
            <label class="flex items-center justify-between text-sm">
              <span>Critical alerts</span>
              <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            </label>
            <div class="pt-1 flex items-center gap-2">
              <button type="button" class="btn btn-red">Save</button>
              <button type="button" class="btn btn-green">Test Alert</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Appearance --}}
    <div class="surface ring-gradient p-5 rounded-2xl transition fade-in md:col-span-2 xl:col-span-1">
      <div class="flex items-start gap-4">
        <div class="icon-chip">🎨</div>
        <div class="flex-1">
          <div class="flex items-center gap-2">
            <h2 class="text-lg font-semibold">Appearance</h2>
            <span class="soft-pill">Theme</span>
          </div>
          <p class="text-sm text-gray-600 mt-1">Customize the application's look and feel.</p>

          <form class="mt-4 space-y-3">
            <label class="flex items-center justify-between text-sm">
              <span>Use compact spacing</span>
              <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            </label>
            <label class="flex items-center justify-between text-sm">
              <span>High-contrast mode</span>
              <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            </label>

            <div class="grid grid-cols-3 gap-2 pt-2">
              <button type="button" class="btn btn-red w-full">Apply</button>
              <button type="button" class="btn btn-blue w-full">Reset</button>
              <a href="{{ route('dashboard') }}" class="btn btn-green w-full text-center">Preview</a>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Security --}}
    <div class="surface ring-gradient p-5 rounded-2xl transition fade-in">
      <div class="flex items-start gap-4">
        <div class="icon-chip">🔒</div>
        <div class="flex-1">
          <div class="flex items-center gap-2">
            <h2 class="text-lg font-semibold">Security</h2>
            <span class="soft-pill">2FA</span>
          </div>
          <p class="text-sm text-gray-600 mt-1">Manage password, sessions, and two-factor authentication.</p>
          <div class="mt-4 flex gap-2">
            <button class="btn btn-red">Enable 2FA</button>
            <button class="btn btn-blue">Sessions</button>
          </div>
        </div>
      </div>
    </div>

    {{-- Billing (optional slot for future) --}}
    <div class="surface ring-gradient p-5 rounded-2xl transition fade-in">
      <div class="flex items-start gap-4">
        <div class="icon-chip">💳</div>
        <div class="flex-1">
          <div class="flex items-center gap-2">
            <h2 class="text-lg font-semibold">Billing</h2>
            <span class="soft-pill">Plan</span>
          </div>
          <p class="text-sm text-gray-600 mt-1">Update your plan, payment method, and invoices.</p>
          <div class="mt-4 flex gap-2">
            <button class="btn btn-blue">Manage Plan</button>
            <button class="btn btn-green">Invoices</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection
