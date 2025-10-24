@extends('layout.mainlayout')

@section('title', 'Sign in · GenRev')

@section('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inria+Sans:wght@400;600;700&family=Kalam:wght@400;700&display=swap');

  :root{
    --bg-offwhite:#f7f7f5;--ink:#0f172a;--muted:#475569;--line:#e5e7eb;
    --red:#dc2626;--green:#16a34a;--blue:#2563eb;--red-50:#fef2f2;--red-600:#dc2626;--green-50:#f0fdf4
  }

  /* utility bits */
  .w-full{width:100%}.mb-6{margin-bottom:1.5rem}.text-center{text-align:center}.text-sm{font-size:.875rem}
  .space-y-5>*+*{margin-top:1.25rem}.flex{display:flex}.items-center{align-items:center}.gap-2{gap:.5rem}
  .muted{color:var(--muted)}

  /* FULL-SCREEN AUTH OVERLAY */
  .page-wrap{
    position:fixed;        /* sit on top of the app shell */
    inset:0;               /* top/right/bottom/left = 0 */
    z-index:1000;          /* above sidebar/header */
    width:100vw;height:100vh;min-height:100vh;
    background:
      radial-gradient(1200px 800px at 10% -10%, rgba(220,38,38,.06), transparent 60%),
      radial-gradient(900px 700px at 110% 110%, rgba(22,163,74,.08), transparent 60%),
      var(--bg-offwhite);
    display:flex;align-items:center;justify-content:center;
    padding:2.5rem 1rem;
    color:var(--ink);
    font-family:'Inria Sans',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
  }

  .card{
    width:100%;
    max-width:560px;                 /* comfy desktop width */
    background:#fff;border:1px solid var(--line);
    border-radius:1.25rem;
    box-shadow:0 1px 2px rgba(0,0,0,.04),0 12px 28px rgba(0,0,0,.06);
    padding:1.85rem;
  }

  .brand-title{font-family:'Kalam',cursive;font-size:1.75rem;line-height:1.2;margin:0 0 .25rem}
  .brand-underline{display:inline-block;position:relative}
  .brand-underline::after{
    content:'';position:absolute;left:0;right:0;bottom:-4px;height:4px;border-radius:999px;
    background:linear-gradient(90deg,var(--red) 0%,var(--green) 60%,var(--blue) 100%);opacity:.25
  }

  .alert{border:1px solid;border-radius:.9rem;padding:.65rem .8rem;display:flex;justify-content:space-between;gap:.75rem;align-items:center}
  .alert-success{background:var(--green-50);border-color:#86efac;color:#065f46}
  .alert-error{background:var(--red-50);border-color:#fca5a5;color:#7f1d1d}
  .close{background:transparent;border:0;font-size:1.15rem;line-height:1;cursor:pointer;opacity:.7}.close:hover{opacity:1}

  .label{font-size:.9rem;color:var(--muted);margin-bottom:.4rem;display:block}
  .input{
    width:100%;background:#fff;color:var(--ink);border:1px solid var(--line);
    border-radius:.8rem;padding:.65rem .85rem;line-height:1.4;transition:box-shadow .15s ease,border-color .15s ease
  }
  .input::placeholder{color:#94a3b8}
  .input:focus{outline:0;border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
  .input[aria-invalid="true"]{border-color:var(--red-600);box-shadow:0 0 0 3px rgba(220,38,38,.12)}

  .field{position:relative}
  .toggle-eye{position:absolute;right:.75rem;top:50%;transform:translateY(-50%);width:20px;height:20px;color:#64748b;cursor:pointer;opacity:.7}
  .toggle-eye:hover{opacity:1}

  .btn{display:inline-flex;justify-content:center;align-items:center;gap:.5rem;font-weight:700;border-radius:.8rem;padding:.7rem 1rem;transition:transform .12s ease,filter .15s ease;border:1px solid transparent}
  .btn[disabled]{opacity:.6;cursor:not-allowed}
  .btn-primary{background:var(--red);color:#fff}.btn-primary:hover{filter:brightness(.97)}
  .btn-link{color:var(--blue);font-weight:600;text-decoration:none}.btn-link:hover{text-decoration:underline}

  /* subtle entrance */
  @media (prefers-reduced-motion:no-preference){
    .fade{animation:fade .2s ease both}
    @keyframes fade{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
  }

  /* responsive polish */
  @media (max-width:480px){ .card{max-width:420px;padding:1.25rem} }
  @media (min-width:1280px){ .card{max-width:600px} }
</style>
@endsection

@section('content')
<div class="page-wrap">
  <div class="card fade" role="region" aria-label="Login form">
    <header class="mb-6 text-center">
      <h2 class="brand-title"><span class="brand-underline">GenRev Login</span></h2>
      <p class="muted text-sm">Welcome back. Please sign in to continue.</p>
    </header>

    {{-- Flash alerts --}}
    @if(session('error'))
      <div class="alert alert-error mb-4" role="alert" aria-live="polite">
        <span>{{ session('error') }}</span>
        <button type="button" class="close" onclick="this.closest('.alert').remove()" aria-label="Dismiss">&times;</button>
      </div>
    @endif
    @if(session('success'))
      <div class="alert alert-success mb-4" role="alert" aria-live="polite">
        <span>{{ session('success') }}</span>
        <button type="button" class="close" onclick="this.closest('.alert').remove()" aria-label="Dismiss">&times;</button>
      </div>
    @endif
    @if(session('status'))
      <div class="alert alert-success mb-4" role="alert" aria-live="polite">
        <span>{{ session('status') }}</span>
        <button type="button" class="close" onclick="this.closest('.alert').remove()" aria-label="Dismiss">&times;</button>
      </div>
    @endif

    {{-- Validation summary --}}
    @if ($errors->any())
      <div class="alert alert-error mb-4" role="alert" aria-live="polite">
        <div>
          <strong>There were some problems with your input.</strong>
          <ul class="text-sm" style="margin:.4rem 0 0 .9rem">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        <button type="button" class="close" onclick="this.closest('.alert').remove()" aria-label="Dismiss">&times;</button>
      </div>
    @endif

    <form id="loginForm" method="POST" action="{{ route('login.submit') }}" class="space-y-5" novalidate>
      @csrf

      <div>
        <label for="email" class="label">Email or Username</label>
        <input
          type="text" id="email" name="email"
          value="{{ old('email') }}" required
          autocomplete="username" spellcheck="false"
          placeholder="you@domain.com or your.username"
          class="input"
          aria-invalid="@error('email') true @else false @enderror"
          inputmode="text"
        >
        @error('email') <p class="text-sm mt-1" style="color:#b91c1c">{{ $message }}</p> @enderror
      </div>

      <div>
        <label for="password" class="label">Password</label>
        <div class="field">
          <input
            type="password"
            id="password"
            name="password"
            required
            autocomplete="current-password"
            placeholder="••••••••"
            class="input pr-10"
            aria-invalid="@error('password') true @else false @enderror"
          >
          <svg id="togglePassword" class="toggle-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="button" tabindex="0" aria-label="Toggle password visibility">
            <path d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/>
          </svg>
        </div>
        @error('password') <p class="text-sm mt-1" style="color:#b91c1c">{{ $message }}</p> @enderror
      </div>

      <label class="flex items-center gap-2 text-sm muted">
        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
        Remember me
      </label>

      <button id="submitBtn" type="submit" class="btn btn-primary w-full">Login</button>

      <p class="text-sm text-center muted">
        @if (Route::has('password.request'))
          <a href="{{ route('password.request') }}" class="btn-link">Forgot your password?</a>
          &nbsp;&middot;&nbsp;
        @endif
        Don’t have an account?
        @if (Route::has('register'))
          <a href="{{ route('register') }}" class="btn-link">Register here</a>
        @endif
      </p>

      {{-- honeypot --}}
      <input type="text" name="website_url" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0;">
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function () {
    const pwd = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');
    const form = document.getElementById('loginForm');
    const btn = document.getElementById('submitBtn');

    if (toggle && pwd) {
      const path = toggle.querySelector('path');
      const OPEN   = 'M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10z';
      const CLOSED = 'M2 5.27L3.28 4 20 20.72 18.73 22l-3.08-3.08A12.8 12.8 0 0112 22C5 22 1 15 1 15s1.7-3.12 4.93-5.56L2 5.27zM12 6c6.73 0 11 7 11 7a24.83 24.83 0 01-4.09 5.16l-2.1-2.1A9.7 9.7 0 0022 13s-4.27-7-10-7c-1.07 0-2.09.17-3.04.49L7.32 5.85A12.2 12.2 0 0112 6z';
      const toggleFn = () => {
        const toText = pwd.type === 'password';
        pwd.type = toText ? 'text' : 'password';
        if (path) path.setAttribute('d', toText ? CLOSED : OPEN);
      };
      toggle.addEventListener('click', toggleFn);
      toggle.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleFn(); }
      });
    }

    if (form && btn) {
      form.addEventListener('submit', function() {
        btn.setAttribute('disabled', 'disabled');
        btn.textContent = 'Signing in...';
      });
    }
  })();
</script>
@endpush
