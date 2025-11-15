@extends('layout.mainlayout')

@section('title', 'Sign in · GenRev')

@section('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inria+Sans:wght@400;600;700&family=Kalam:wght@400;700&display=swap');

  :root{
    --bg-offwhite:#f7f7f5;--ink:#0f172a;--muted:#475569;--line:#e5e7eb;
    --red:#dc2626;--green:#16a34a;--blue:#2563eb;--red-50:#fef2f2;--green-50:#f0fdf4
  }

  /* ===== neutralize/disable the app shell JUST on this page ===== */
  html, body { height:100%; margin:0; padding:0; overflow:hidden; }
  /* common sidebar/header containers in your layout */
  .app-sidebar, .sidebar, .sidebar-wrap, .layout-sidebar, [id*="sidebar"],
  .app-header, .main-header, header.topbar, header.app-header, header.main-header {
    display:none !important;
  }
  /* clear any layout offsets caused by the shell */
  #app, .layout, .wrapper, .content, main {
    margin:0 !important;
    padding:0 !important;
  }

  /* ===== FULL-SCREEN AUTH LAYER ===== */
  .page-wrap{
    position:fixed !important; /* override parent transforms */
    inset:0 !important;
    z-index:999999;            /* above any layout chrome */
    width:100vw; height:100vh; min-height:100vh;
    display:flex; align-items:center; justify-content:center;
    color:var(--ink);
    font-family:'Inria Sans',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
  }

  /* Background video (always behind) */
  .bg-video-wrap{
    position:absolute; inset:0; z-index:0; overflow:hidden; background:var(--bg-offwhite);
    pointer-events:none; /* keep it purely decorative */
  }
  .bg-video{
    position:absolute; inset:0; width:100%; height:100%; object-fit:cover;
    filter:brightness(.78);  /* keep text readable */
  }

  /* Your login card */
  .card{
    position:relative; z-index:2;
    width:100%; max-width:560px;
    background:rgba(255,255,255,.86);
    border:1px solid var(--line);
    border-radius:1.25rem;
    box-shadow:0 1px 2px rgba(0,0,0,.04),0 12px 28px rgba(0,0,0,.1);
    padding:1.85rem;
    backdrop-filter:blur(10px);
    transition:transform .18s ease, box-shadow .18s ease;
  }

  .brand-title{font-family:'Kalam',cursive;font-size:1.75rem;margin:0 0 .25rem;text-align:center}
  .brand-underline{display:inline-block;position:relative}
  .brand-underline::after{
    content:'';position:absolute;left:0;right:0;bottom:-4px;height:4px;border-radius:999px;
    background:linear-gradient(90deg,var(--red) 0%,var(--green) 60%,var(--blue) 100%);opacity:.25
  }

  .muted{color:var(--muted)} .text-sm{font-size:.875rem}
  .space-y-5>*+*{margin-top:1.25rem}
  .label{font-size:.9rem;color:var(--muted);margin-bottom:.4rem;display:block}

  .input{
    width:100%; background:#fff; color:var(--ink); border:1px solid var(--line);
    border-radius:.8rem; padding:.65rem .85rem; line-height:1.4; transition:all .15s ease;
  }
  .input:focus{outline:0;border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.15)}

  .field{position:relative}
  .toggle-eye{position:absolute;right:.75rem;top:50%;transform:translateY(-50%);width:20px;height:20px;color:#64748b;cursor:pointer;opacity:.7}
  .toggle-eye:hover{opacity:1}

  .btn{
    display:inline-flex;justify-content:center;align-items:center;gap:.5rem;
    font-weight:700;border-radius:.8rem;padding:.7rem 1rem;transition:filter .15s ease, transform .1s ease;
  }
  .btn-primary{background:var(--red);color:#fff;border:none;width:100%}
  .btn-primary:hover{filter:brightness(.95);transform:translateY(-1px)}
  .btn-primary:disabled{opacity:.7;cursor:not-allowed;transform:none}

  .btn-link{color:var(--blue);font-weight:600;text-decoration:none}
  .btn-link:hover{text-decoration:underline}

  .text-center{text-align:center}

  /* ===== Alert styling (glassy) ===== */
  .alert{
    position:relative;
    border-radius:.9rem;
    padding:.65rem .9rem;
    font-size:.85rem;
    display:flex;
    align-items:flex-start;
    gap:.5rem;
    border:1px solid rgba(0,0,0,.04);
    backdrop-filter:blur(8px);
  }
  .alert-error{
    background:linear-gradient(135deg,rgba(248,113,113,.14),rgba(248,250,252,.85));
    border-color:rgba(239,68,68,.7);
    color:#7f1d1d;
  }
  .alert-success{
    background:linear-gradient(135deg,rgba(34,197,94,.14),rgba(248,250,252,.9));
    border-color:rgba(22,163,74,.7);
    color:#14532d;
  }
  .alert .close{
    margin-left:auto;
    border:none;
    background:transparent;
    font-size:1rem;
    cursor:pointer;
    color:inherit;
    padding:0 .1rem;
    line-height:1;
    opacity:.7;
  }
  .alert .close:hover{opacity:1}

  /* Shake animation when there is an error */
  .shake-once{
    animation:shake .4s ease;
  }
  @keyframes shake{
    0%{transform:translateX(0);}
    20%{transform:translateX(-4px);}
    40%{transform:translateX(4px);}
    60%{transform:translateX(-3px);}
    80%{transform:translateX(3px);}
    100%{transform:translateX(0);}
  }

  @media (max-width:480px){ .card{max-width:400px;padding:1.25rem} }
</style>
@endsection

@section('content')
<div class="page-wrap">
  <!-- Background video layer -->
  <div class="bg-video-wrap" aria-hidden="true">
    <video class="bg-video" autoplay muted loop playsinline preload="auto" poster="">
      <source src="{{ asset('videos/Background.mp4') }}" type="video/mp4">
    </video>
  </div>

  <!-- Login Card -->
  <div class="card" id="loginCard">
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

    {{-- Validation errors, including "Your account has been blocked / deactivated" --}}
    @if($errors->any())
      @php $firstError = $errors->first() ?? ''; @endphp
      <div class="alert alert-error mb-4" role="alert" aria-live="assertive">
        <span>
          {{ $firstError }}
          @if(\Illuminate\Support\Str::contains(strtolower($firstError), 'blocked')
              || \Illuminate\Support\Str::contains(strtolower($firstError), 'deactivated'))
            &nbsp;Please contact your administrator if you believe this is a mistake.
          @endif
        </span>
        <button type="button" class="close" onclick="this.closest('.alert').remove()" aria-label="Dismiss">&times;</button>
      </div>
    @endif

    <form id="loginForm" method="POST" action="{{ route('login.submit') }}" class="space-y-5" novalidate>
      @csrf

      <div>
        <label for="email" class="label">Email or Username</label>
        <input type="text" id="email" name="email" value="{{ old('email') }}" required
               autocomplete="username" spellcheck="false"
               placeholder="you@domain.com or your.username"
               class="input">
      </div>

      <div>
        <label for="password" class="label">Password</label>
        <div class="field">
          <input type="password" id="password" name="password" required
                 autocomplete="current-password" placeholder="••••••••" class="input pr-10">
          <svg id="togglePassword" class="toggle-eye" xmlns="http://www.w3.org/2000/svg"
               viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="button" tabindex="0" aria-label="Toggle password visibility">
            <path d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/>
          </svg>
        </div>
      </div>

      <label class="flex items-center gap-2 text-sm muted">
        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
        Remember me
      </label>

      <button id="submitBtn" type="submit" class="btn btn-primary">Login</button>

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

@section('auth_page', true)

@section('background')
  <div class="fixed inset-0 -z-10">
    <video class="w-full h-full object-cover" autoplay muted loop playsinline preload="auto">
      <source src="{{ asset('videos/Background.mp4') }}" type="video/mp4">
    </video>
  </div>
@endsection

@push('scripts')
<script>
  (function () {
    const pwd = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');
    const form = document.getElementById('loginForm');
    const btn = document.getElementById('submitBtn');
    const card = document.getElementById('loginCard');

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

    // Shake the card when there is an error
    const hasErrorAlert = document.querySelector('.alert-error');
    if (card && hasErrorAlert) {
      card.classList.add('shake-once');
      setTimeout(() => card.classList.remove('shake-once'), 500);
    }

    // Respect reduced motion
    const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
    const vid = document.querySelector('.bg-video');
    if (mq.matches && vid) { vid.pause(); }
  })();
</script>
@endpush
