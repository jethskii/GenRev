@extends('layout.mainlayout')

@section('title', 'Sign in · GenRev')

@section('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inria+Sans:wght@400;600;700&family=Kalam:wght@400;700&display=swap');

  :root{
    --bg-offwhite:#f7f7f5; --ink:#0f172a; --muted:#475569; --line:#e5e7eb;
    --red:#dc2626; --green:#16a34a; --blue:#2563eb;
    --red-50:#fef2f2; --red-600:#dc2626; --green-50:#f0fdf4;
  }

  .w-full{width:100%} .mb-6{margin-bottom:1.5rem} .text-center{text-align:center}
  .text-sm{font-size:.875rem} .space-y-5>*+*{margin-top:1.25rem}
  .flex{display:flex} .items-center{align-items:center} .gap-2{gap:.5rem}

  .page-wrap{
    min-height:100vh;
    background:
      radial-gradient(1200px 800px at 10% -10%, rgba(220,38,38,.06), transparent 60%),
      radial-gradient(900px 700px at 110% 110%, rgba(22,163,74,.08), transparent 60%),
      var(--bg-offwhite);
    display:flex;align-items:center;justify-content:center;
    padding:2.75rem 1rem;color:var(--ink);
    font-family:'Inria Sans', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
  }

  .card{
    width:100%;max-width:440px;background:#fff;border:1px solid var(--line);
    border-radius:1.25rem;box-shadow:0 1px 2px rgba(0,0,0,.04),0 12px 28px rgba(0,0,0,.06);
    padding:1.75rem;
  }

  .brand-title{font-family:'Kalam',cursive;font-size:1.75rem;line-height:1.2;margin:0 0 .25rem}
  .brand-underline{display:inline-block;position:relative}
  .brand-underline::after{content:'';position:absolute;left:0;right:0;bottom:-4px;height:4px;border-radius:999px;
    background:linear-gradient(90deg,var(--red) 0%,var(--green) 60%,var(--blue) 100%);opacity:.25}

  .alert{border:1px solid;border-radius:.9rem;padding:.65rem .8rem;display:flex;justify-content:space-between;gap:.75rem;align-items:center}
  .alert-success{background:var(--green-50);border-color:#86efac;color:#065f46}
  .alert-error{background:var(--red-50);border-color:#fca5a5;color:#7f1d1d}
  .close{background:transparent;border:0;font-size:1.15rem;line-height:1;cursor:pointer;opacity:.7}
  .close:hover{opacity:1}

  .label{font-size:.9rem;color:var(--muted);margin-bottom:.4rem;display:block}
  .input{width:100%;background:#fff;color:var(--ink);border:1px solid var(--line);border-radius:.8rem;
    padding:.65rem .85rem;line-height:1.4;transition:box-shadow .15s ease,border-color .15s ease}
  .input::placeholder{color:#94a3b8}
  .input:focus{outline:0;border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
  .input[aria-invalid="true"]{border-color:var(--red-600);box-shadow:0 0 0 3px rgba(220,38,38,.12)}
  .field{position:relative}
  .toggle-eye{position:absolute;right:.75rem;top:50%;transform:translateY(-50%);width:20px;height:20px;color:#64748b;cursor:pointer;opacity:.7}
  .toggle-eye:hover{opacity:1}

  .btn{display:inline-flex;justify-content:center;align-items:center;gap:.5rem;font-weight:700;border-radius:.8rem;
    padding:.7rem 1rem;transition:transform .12s ease,filter .15s ease;border:1px solid transparent}
  .btn[disabled]{opacity:.6;cursor:not-allowed}
  .btn-primary{background:var(--red);color:#fff}
  .btn-primary:hover{filter:brightness(.97)}
  .btn-link{color:var(--blue);font-weight:600;text-decoration:none}
  .btn-link:hover{text-decoration:underline}
  .muted{color:var(--muted)}

  @media (prefers-reduced-motion:no-preference){
    .fade{animation:fade .2s ease both}
    @keyframes fade{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
  }
</style>
@endsection

@section('content')
<div class="page-wrap">
  <div class="card fade" role="region" aria-label="Login form">
    <header class="mb-6 text-center">
      <h2 class="brand-title"><span class="brand-underline">Admin Login</span></h2>
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

      {{-- Email or Username --}}
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

      {{-- Password --}}
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
          <svg id="togglePassword"
               class="toggle-eye"
               xmlns="http://www.w3.org/2000/svg"
               viewBox="0 0 24 24"
               fill="currentColor" aria-hidden="true" role="button" tabindex="0" aria-pressed="false" aria-label="Toggle password visibility">
            <path d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/>
          </svg>
        </div>
        @error('password') <p class="text-sm mt-1" style="color:#b91c1c">{{ $message }}</p> @enderror
      </div>

      {{-- Remember --}}
      <label class="flex items-center gap-2 text-sm muted">
        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
        Remember me
      </label>

      {{-- Submit --}}
      <button id="submitBtn" type="submit" class="btn btn-primary w-full">Login</button>

      <p class="text-sm text-center muted">
        @if (Route::has('password.request'))
          <a href="{{ route('password.request') }}" class="btn-link">Forgot your password?</a>
          &nbsp;&middot;&nbsp;
        @endif
        Don’t have an account?
        @if (Route::has('register'))
          <a href="{{ route('register') }}" class="btn-link">Register here</a>
        @else
          <a href="{{ url('/register') }}" class="btn-link">Register here</a>
        @endif
      </p>

      {{-- Honeypot --}}
      <input type="text" name="website_url" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0;">
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function () {
    const pwd    = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');
    const form   = document.getElementById('loginForm');
    const btn    = document.getElementById('submitBtn');

    if (toggle && pwd) {
      const path = toggle.querySelector('path');
      // Simple eye/eye-off swap via path d attribute
      const OPEN   = 'M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10z';
      const CLOSED = 'M3.28 2L22 20.72 20.72 22l-3.2-3.2C15.45 20.1 13.8 21 12 21 5 21 1 14 1 14s1.74-3.17 5.03-5.62L2 4.72 3.28 3.44 7.4 7.55A10.9 10.9 0 0112 6c6.73 0 11 7 11 7a25.2 25.2 0 01-4.38 5.43l-2.13-2.13A8 8 0 0012 8a7.9 7.9 0 00-2.85.53L3.28 2z';

      const toggleFn = () => {
        const reveal = pwd.type === 'password';
        pwd.type = reveal ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
        if (path) path.setAttribute('d', reveal ? CLOSED : OPEN);
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
