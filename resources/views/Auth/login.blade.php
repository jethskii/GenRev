@extends('layout.mainlayout')

@section('styles')
<style>
  /* Fonts */
  @import url('https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&display=swap');
  @import url('https://fonts.googleapis.com/css2?family=Inria+Sans:wght@300;400;700&display=swap');

  /* Page liquid background (scoped to wrapper) */
  .liquid-bg {
    position: relative;
    min-height: 100vh;
    background: linear-gradient(135deg, #1F1E1E 0%, #001C00 100%);
    font-family: 'Inria Sans', sans-serif;
    overflow: hidden;
  }
  .liquid-bg::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(
      to bottom right,
      rgba(18, 108, 7, 0.15) 0%,
      rgba(113, 200, 98, 0.15) 25%,
      rgba(210, 220, 50, 0.12) 50%,
      rgba(113, 200, 98, 0.15) 75%,
      rgba(10, 56, 14, 0.15) 100%
    );
    transform: rotate(30deg);
    animation: liquidFlow 15s linear infinite;
    z-index: 0;
    opacity: 0.5;
  }
  @keyframes liquidFlow {
    0% { transform: rotate(30deg) translate(-10%, -10%); }
    50% { transform: rotate(30deg) translate(10%, 10%); }
    100% { transform: rotate(30deg) translate(-10%, -10%); }
  }

  /* Glass card */
  .liquid-card {
    position: relative;
    overflow: hidden;
    border-radius: 20px;
    backdrop-filter: blur(10px);
    background: rgba(31, 30, 30, 0.7);
    box-shadow: 0 8px 32px rgba(0, 28, 0, 0.3);
    transition: all 0.5s ease;
    z-index: 1;
  }
  .liquid-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(45deg, rgba(4,119,5,0.10) 0%, rgba(237,209,0,0.10) 50%, rgba(4,119,5,0.10) 100%);
    animation: cardShine 8s ease infinite;
    z-index: -1;
  }
  @keyframes cardShine { 0% {opacity:.3;} 50% {opacity:.1;} 100% {opacity:.3;} }

  /* Inputs */
  .liquid-input {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #fff;
    border-radius: 10px;
    padding: 10px 14px;
    width: 100%;
    transition: all .3s ease;
  }
  .liquid-input::placeholder { color: rgba(255,255,255,.55); }
  .liquid-input:focus { outline: none; border-color:#047705; box-shadow:0 0 0 2px rgba(4,119,5,.3); background: rgba(255,255,255,.10); }

  /* Labels */
  .liquid-label { color: rgba(255,255,255,.85); font-size:.9rem; margin-bottom:.35rem; display:block; }

  /* Buttons */
  .liquid-btn {
    position: relative;
    overflow: hidden;
    border: none;
    border-radius: 12px;
    background: linear-gradient(90deg, #047705 0%, #0aad0a 100%);
    color: #fff;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(4,119,5,0.4);
    transition: transform .2s ease, box-shadow .2s ease;
  }
  .liquid-btn::before {
    content: '';
    position: absolute; inset: 0; left: -100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.25), transparent);
    transition: left .6s ease;
  }
  .liquid-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(4,119,5,0.45); }
  .liquid-btn:hover::before { left: 100%; }

  /* Alerts */
  .liquid-alert { border:1px solid; border-radius:12px; padding:.75rem 1rem; display:flex; justify-content:space-between; align-items:center; backdrop-filter: blur(6px); animation: slideIn .3s ease forwards; }
  .alert-error { background: linear-gradient(90deg, rgba(220,38,38,.35), rgba(153,27,27,.35)); border-color: rgba(239,68,68,.5); color:#fee2e2; }
  .alert-success { background: linear-gradient(90deg, rgba(22,163,74,.35), rgba(21,128,61,.35)); border-color: rgba(34,197,94,.5); color:#dcfce7; }
  @keyframes slideIn { from {opacity:0; transform: translateY(-6px);} to {opacity:1; transform: translateY(0);} }

  /* Password toggle icon */
  .toggle-eye { position:absolute; right:.75rem; top:50%; transform: translateY(-50%); cursor:pointer; opacity:.7; }
  .toggle-eye:hover{ opacity:1; }

  /* Title */
  .liquid-title { font-family: 'Kalam', cursive; text-shadow: -2px 1px 0px #047705; }
</style>
@endsection

@section('content')
<div class="liquid-bg flex justify-center items-center px-4">
  <div class="liquid-card w-full max-w-md p-8 border border-white/20">

    <h2 class="text-3xl font-bold text-center mb-6 text-white liquid-title">Admin Login</h2>

    @if(session('error'))
      <div class="liquid-alert alert-error mb-4">
        <span>{{ session('error') }}</span>
        <button type="button" onclick="this.parentElement.remove()" aria-label="Dismiss" class="text-red-100">&times;</button>
      </div>
    @endif

    @if(session('success'))
      <div class="liquid-alert alert-success mb-4">
        <span>{{ session('success') }}</span>
        <button type="button" onclick="this.parentElement.remove()" aria-label="Dismiss" class="text-green-100">&times;</button>
      </div>
    @endif

    <form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
      @csrf

      {{-- Email Field --}}
      <div>
        <label for="email" class="liquid-label">Email Address</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus class="liquid-input" placeholder="you@domain.com">
        @error('email')
          <p class="text-red-300 text-xs mt-1">{{ $message }}</p>
        @enderror
      </div>

      {{-- Password Field --}}
      <div>
        <label for="password" class="liquid-label">Password</label>
        <div class="relative">
          <input type="password" id="password" name="password" required class="liquid-input pr-10" placeholder="••••••••">
          <svg class="toggle-eye" id="togglePassword" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/>
          </svg>
        </div>
        @error('password')
          <p class="text-red-300 text-xs mt-1">{{ $message }}</p>
        @enderror
      </div>

      {{-- Login Button --}}
      <button type="submit" class="liquid-btn w-full py-3">Login</button>

      <p class="text-sm text-center text-gray-300">
        Don’t have an account?
        <a href="{{ route('register') }}" class="text-[#A9E34B] hover:underline">Register here</a>
      </p>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Password toggle
  (function(){
    const input = document.getElementById('password');
    const btn = document.getElementById('togglePassword');
    if (input && btn) {
      btn.addEventListener('click', () => {
        const isPwd = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPwd ? 'text' : 'password');
      });
    }
  })();
</script>
@endpush
