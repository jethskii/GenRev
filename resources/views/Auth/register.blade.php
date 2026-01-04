{{-- resources/views/auth/register.blade.php --}}
@extends('layout.mainlayout')

@section('title', 'Register · GenRev')

@section('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inria+Sans:wght@400;600;700&family=Kalam:wght@400;700&display=swap');

  :root{
    --bg-offwhite:#f7f7f5;
    --ink:#0f172a;
    --muted:#475569;
    --line:#e5e7eb;

    --red:#dc2626;
    --green:#16a34a;
    --blue:#2563eb;

    --red-50:#fef2f2;
    --red-600:#dc2626;
    --green-50:#f0fdf4;
  }

  .auth-card{
    width:100%;
    max-width:560px;
    background:linear-gradient(180deg,#ffe7df,#fff9e6);
    border-radius:1.5rem;
    border:1px solid var(--line);
    padding:1.85rem;
    box-shadow:0 1px 2px rgba(15,23,42,.08),0 16px 40px rgba(15,23,42,.18);
    backdrop-filter:blur(10px);
    font-family:'Inria Sans',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
  }

  .title{
    font-family:'Kalam',cursive;
    font-size:1.9rem;
    margin:0 0 .25rem;
    text-align:center;
  }
  .underline{
    display:inline-block;
    position:relative;
  }
  .underline::after{
    content:'';position:absolute;left:0;right:0;bottom:-4px;
    height:4px;border-radius:999px;
    background:linear-gradient(90deg,var(--red),var(--green) 60%,var(--blue));
    opacity:.35;
  }
  .sub{
    color:var(--muted);
    font-size:.9rem;
    text-align:center;
    margin:.35rem 0 1rem;
  }

  .alert{
    border-radius:.9rem;
    padding:.65rem .8rem;
    display:flex;
    justify-content:space-between;
    gap:.75rem;
    align-items:center;
    margin-bottom:1rem;
    border:1px solid transparent;
    font-size:.85rem;
  }
  .alert-success{background:var(--green-50);border-color:#86efac;color:#065f46;}
  .alert-error{background:var(--red-50);border-color:#fca5a5;color:#7f1d1d;}
  .close{
    background:transparent;border:0;font-size:1.15rem;line-height:1;
    cursor:pointer;opacity:.7;
  }
  .close:hover{opacity:1;}

  .label{
    font-size:.9rem;color:var(--muted);margin-bottom:.4rem;display:block;
  }
  .input{
    width:100%;background:#fff;color:var(--ink);
    border:1px solid var(--line);border-radius:.8rem;
    padding:.65rem .85rem;line-height:1.4;
    transition:border-color .15s ease, box-shadow .15s ease;
  }
  .input::placeholder{color:#94a3b8;}
  .input:focus{
    outline:0;border-color:var(--blue);
    box-shadow:0 0 0 3px rgba(37,99,235,.15);
  }
  .input[aria-invalid="true"]{
    border-color:var(--red-600);
    box-shadow:0 0 0 3px rgba(220,38,38,.12);
  }
  .row{margin-bottom:1rem;}
  .help{font-size:.75rem;color:var(--muted);margin-top:.35rem;}

  .field{position:relative;}
  .toggle-eye{
    position:absolute;right:.75rem;top:50%;transform:translateY(-50%);
    width:20px;height:20px;color:#64748b;cursor:pointer;opacity:.8;
  }
  .toggle-eye:hover{opacity:1;}

  /* OTP row like the screenshot */
  .otp-row{
    display:flex;
    gap:.6rem;
    align-items:center;
  }
  .otp-row .otp-input{
    flex:1;
  }
  .otp-send-btn{
    white-space:nowrap;
    border-radius:999px;
    padding:.55rem 1.1rem;
    border:none;
    font-size:.8rem;
    font-weight:700;
    background:linear-gradient(90deg,#f97316,#ef4444);
    color:#fff;
    cursor:pointer;
    box-shadow:0 6px 14px rgba(248,113,113,.4);
    transition:transform .12s ease,filter .12s ease;
  }
  .otp-send-btn:hover{filter:brightness(.97);}
  .otp-send-btn[disabled]{opacity:.65;cursor:not-allowed;box-shadow:none;}

  .btn{
    display:inline-flex;justify-content:center;align-items:center;gap:.5rem;
    width:100%;font-weight:700;border-radius:.9rem;
    padding:.8rem 1rem;border:1px solid transparent;
    background:linear-gradient(90deg,#f97316,#ef4444);
    color:#fff;
    transition:transform .12s ease,filter .15s ease;
    margin-top:.35rem;
  }
  .btn:hover{filter:brightness(.97);}
  .btn[disabled]{opacity:.6;cursor:not-allowed;}

  .link{color:var(--blue);font-weight:600;text-decoration:none;}
  .link:hover{text-decoration:underline;}

  .meter{
    height:6px;border-radius:999px;
    background:#eef2ff;overflow:hidden;margin-top:.5rem;
  }
  .meter>i{
    display:block;height:100%;width:0;
    background:linear-gradient(90deg,#ef4444,#f59e0b,#22c55e);
    transition:width .2s ease;
  }

  @media (prefers-reduced-motion:no-preference){
    .fade{animation:fade .2s ease both;}
    @keyframes fade{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
  }
  @media (max-width:480px){
    .auth-card{max-width:420px;padding:1.25rem;}
    .otp-row{flex-direction:column;align-items:stretch;}
    .otp-send-btn{width:100%;text-align:center;}
  }
</style>
@endsection

@section('background')
  <div class="fixed inset-0 -z-10">
    <video class="w-full h-full object-cover" autoplay muted loop playsinline preload="auto">
      <source src="{{ asset('videos/Background.mp4') }}" type="video/mp4">
    </video>
  </div>
@endsection

@section('content')
  <div class="auth-card fade" role="region" aria-label="Registration form">

    <h2 class="title"><span class="underline">Register New Account</span></h2>
    <p class="sub">
      Step 1. Send a code to the Masters Admin. Step 2. Enter the OTP and create your account.
    </p>

    {{-- Server flash messages (for final submit or full-page errors) --}}
    @if(session('success'))
      <div class="alert alert-success" role="alert" aria-live="polite">
        <span>{{ session('success') }}</span>
        <button type="button" class="close" onclick="this.closest('.alert').remove()" aria-label="Dismiss">&times;</button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-error" role="alert" aria-live="polite">
        <span>{{ session('error') }}</span>
        <button type="button" class="close" onclick="this.closest('.alert').remove()" aria-label="Dismiss">&times;</button>
      </div>
    @endif

    {{-- Validation summary for full form (create account) --}}
    @if ($errors->any())
      <div class="alert alert-error" role="alert" aria-live="polite">
        <div>
          <strong>Please fix the following:</strong>
          <ul class="text-sm" style="margin:.4rem 0 0 .9rem">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        <button type="button" class="close" onclick="this.closest('.alert').remove()" aria-label="Dismiss">&times;</button>
      </div>
    @endif

    {{-- AJAX area for OTP success / errors (no reload) --}}
    <div id="otpSuccess" class="alert alert-success" style="display:none;">
      <span id="otpSuccessMsg"></span>
      <button type="button" class="close" onclick="this.closest('.alert').style.display='none'" aria-label="Dismiss">&times;</button>
    </div>
    <div id="otpErrors" class="alert alert-error" style="display:none;">
      <div><strong>Fix the following:</strong>
        <ul id="otpErrorsList" class="text-sm" style="margin:.35rem 0 0 .9rem"></ul>
      </div>
      <button type="button" class="close" onclick="this.closest('.alert').style.display='none'" aria-label="Dismiss">&times;</button>
    </div>

    <form method="POST" action="{{ route('register.submit') }}" id="regForm" novalidate>
      @csrf

      {{-- Full name --}}
      <div class="row">
        <label for="name" class="label">Full Name</label>
        <input
          type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
          autocomplete="name" placeholder="Jane D. Santos" class="input"
          aria-invalid="@error('name') true @else false @enderror"
          aria-describedby="@error('name') nameHelp @enderror">
        @error('name') <p id="nameHelp" class="help" style="color:#b91c1c">{{ $message }}</p> @enderror
      </div>

      {{-- Username --}}
      <div class="row">
        <label for="username" class="label">Username</label>
        <input
          type="text" id="username" name="username" value="{{ old('username') }}"
          placeholder="jane.santos" class="input" inputmode="latin"
          aria-invalid="@error('username') true @else false @enderror"
          aria-describedby="usernameHelp @error('username') usernameErr @enderror">
        <p id="usernameHelp" class="help">Leave blank to use your email as the username.</p>
        @error('username') <p id="usernameErr" class="help" style="color:#b91c1c">{{ $message }}</p> @enderror
      </div>

      {{-- Email --}}
      <div class="row">
        <label for="email" class="label">Email Address</label>
        <input
          type="email" id="email" name="email" value="{{ old('email') }}" required
          autocomplete="email" placeholder="you@domain.com" class="input"
          aria-invalid="@error('email') true @else false @enderror"
          aria-describedby="@error('email') emailHelp @enderror">
        @error('email') <p id="emailHelp" class="help" style="color:#b91c1c">{{ $message }}</p> @enderror
      </div>

      {{-- Password --}}
      <div class="row">
        <label for="password" class="label">Password</label>
        <div class="field">
          <input
            type="password" id="password" name="password" required
            autocomplete="new-password" placeholder="••••••••" class="input pr-10"
            aria-invalid="@error('password') true @else false @enderror"
            aria-describedby="pwHint @error('password') pwErr @enderror">
          <svg id="togglePass" class="toggle-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" role="button" tabindex="0" aria-label="Toggle password visibility"><path d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/></svg>
        </div>
        <div class="meter" aria-hidden="true"><i id="pwBar"></i></div>
        <p id="pwHint" class="help">Use at least 8 characters with a mix of letters and numbers.</p>
        @error('password') <p id="pwErr" class="help" style="color:#b91c1c">{{ $message }}</p> @enderror
      </div>

      {{-- Confirm Password --}}
      <div class="row">
        <label for="password_confirmation" class="label">Confirm Password</label>
        <div class="field">
          <input
            type="password" id="password_confirmation" name="password_confirmation" required
            autocomplete="new-password" placeholder="Repeat password" class="input pr-10"
            aria-describedby="matchHint">
          <svg id="togglePass2" class="toggle-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" role="button" tabindex="0" aria-label="Toggle password visibility"><path d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/></svg>
        </div>
        <p id="matchHint" class="help">Passwords must match.</p>
      </div>

      {{-- User Role --}}
      <div class="row">
        <label for="role" class="label">User Role</label>
        <select id="role" name="role" required class="input"
                aria-invalid="@error('role') true @else false @enderror"
                aria-describedby="@error('role') roleHelp @enderror">
          <option value="" disabled {{ old('role') ? '' : 'selected' }}>Select a role</option>
          <option value="production manager" {{ old('role')==='production manager'?'selected':'' }}>Production Manager</option>
          <option value="sales" {{ old('role')==='sales'?'selected':'' }}>Sales</option>
          <option value="inventory" {{ old('role')==='inventory'?'selected':'' }}>Inventory</option>
        </select>
        @error('role') <p id="roleHelp" class="help" style="color:#b91c1c">{{ $message }}</p> @enderror
      </div>

      {{-- OTP + Send OTP button (AJAX, no reload) --}}
      <div class="row">
        <label for="otp" class="label">OTP</label>
        <div class="otp-row">
          <input
            type="text" id="otp" name="otp"
            maxlength="6" inputmode="numeric" autocomplete="one-time-code"
            placeholder="Enter the 6 digit code"
            value="{{ old('otp') }}"
            class="input otp-input">
          <button type="button" id="sendOtpBtn" class="otp-send-btn">
            Send OTP
          </button>
        </div>
        <p class="help">Ask the Masters Admin for the code after it’s sent.</p>
      </div>

      {{-- Hidden flag so backend can check that OTP was requested --}}
      <input type="hidden" name="otp_requested" id="otp_requested"
             value="{{ session()->has('pending_registration') ? '1' : '0' }}">

      {{-- Final submit --}}
      <button
        type="submit"
        id="primaryBtn"
        class="btn">
        Create Account
      </button>

      <p class="sub" style="margin-top:.9rem">
        Already have an account?
        <a href="{{ route('login') }}" class="link">Login here</a>
      </p>

      {{-- Honeypot --}}
      <input type="text" name="website_url" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0;">
    </form>
  </div>
@endsection

@section('auth_page', true)

@push('scripts')
<script>
  (function(){
    const $ = (s,root=document)=>root.querySelector(s);

    const form       = $('#regForm');
    const email      = $('#email');
    const uname      = $('#username');
    const p1         = $('#password');
    const p2         = $('#password_confirmation');
    const bar        = $('#pwBar');
    const hint       = $('#pwHint');
    const matchHint  = $('#matchHint');
    const t1         = $('#togglePass');
    const t2         = $('#togglePass2');
    const sendOtpBtn = $('#sendOtpBtn');
    const otpInput   = $('#otp');
    const otpFlag    = $('#otp_requested');

    const otpSuccessBox = document.getElementById('otpSuccess');
    const otpSuccessMsg = document.getElementById('otpSuccessMsg');
    const otpErrorBox   = document.getElementById('otpErrors');
    const otpErrorsList = document.getElementById('otpErrorsList');

    // Suggest username from email if blank
    if (email && uname) {
      email.addEventListener('blur', () => {
        if (!uname.value.trim() && email.value.includes('@')) {
          uname.value = email.value.split('@')[0].replace(/[^a-zA-Z0-9._-]/g,'');
        }
      });
    }

    // Password strength + match
    function score(v){
      let s=0;
      if(v.length>=8) s+=30;
      if(/[A-Z]/.test(v)) s+=20;
      if(/[a-z]/.test(v)) s+=20;
      if(/[0-9]/.test(v)) s+=15;
      if(/[^A-Za-z0-9]/.test(v)) s+=15;
      return Math.min(100,s);
    }
    function draw(){
      if (!p1 || !bar || !hint) return;
      const v = p1.value || '';
      const s = score(v);
      bar.style.width = (s || 5) + '%';
      if (s < 40) {
        hint.textContent = 'Weak. Add length and variety.';
      } else if (s < 70) {
        hint.textContent = 'Medium. Add more variety.';
      } else {
        hint.textContent = 'Strong password.';
      }
    }
    function match(){
      if (!p1 || !p2 || !matchHint) return;
      if (!p2.value) { matchHint.textContent='Passwords must match.'; matchHint.style.color=''; return; }
      const ok = p1.value && p1.value === p2.value;
      matchHint.textContent = ok ? 'Passwords match.' : 'Passwords do not match.';
      matchHint.style.color = ok ? '#16a34a' : '#b91c1c';
    }
    p1 && p1.addEventListener('input', ()=>{ draw(); match(); });
    p2 && p2.addEventListener('input', match);
    draw();

    // Toggle show/hide password
    const toggler = (icon,input)=>{
      if(!icon||!input) return;
      const open='M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10z';
      const closed='M2 5.27L3.28 4 20 20.72 18.73 22l-3.08-3.08A12.8 12.8 0 0112 22C5 22 1 15 1 15s1.7-3.12 4.93-5.56L2 5.27zM12 6c6.73 0 11 7 11 7a24.83 24.83 0 01-4.09 5.16l-2.1-2.1A9.7 9.7 0 0022 13s-4.27-7-10-7c-1.07 0-2.09.17-3.04.49L7.32 5.85A12.2 12.2 0 0112 6z';
      const path = icon.querySelector('path');
      const swap = ()=>{
        const isPw = input.type==='password';
        input.type = isPw ? 'text' : 'password';
        if (path) path.setAttribute('d', isPw ? closed : open);
      };
      icon.addEventListener('click', swap);
      icon.addEventListener('keydown', e => {
        if (e.key==='Enter' || e.key===' ') { e.preventDefault(); swap(); }
      });
    };
    toggler(t1,p1); toggler(t2,p2);

    // SEND OTP (AJAX) – no page reload
    if (sendOtpBtn && form) {
      sendOtpBtn.addEventListener('click', function () {
        if (!form) return;

        if (otpErrorBox && otpErrorsList) {
          otpErrorBox.style.display = 'none';
          otpErrorsList.innerHTML   = '';
        }
        if (otpSuccessBox) {
          otpSuccessBox.style.display = 'none';
        }

        const fd = new FormData(form);
        // marker for backend if ever needed
        fd.set('action', 'request');

        const tokenInput = form.querySelector('input[name=_token]');
        const csrfToken  = tokenInput ? tokenInput.value : '';

        sendOtpBtn.disabled = true;
        sendOtpBtn.textContent = 'Sending…';

        fetch("{{ route('register.otp') }}", {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: fd
        }).then(async res => {
          const data = await res.json().catch(() => ({}));

          if (res.status === 422 && data.errors && otpErrorBox && otpErrorsList) {
            // validation errors
            otpErrorsList.innerHTML = '';
            Object.values(data.errors).forEach(msgArr => {
              msgArr.forEach(msg => {
                const li = document.createElement('li');
                li.textContent = msg;
                otpErrorsList.appendChild(li);
              });
            });
            otpErrorBox.style.display = 'flex';
          } else if (!res.ok || data.ok === false) {
            if (otpErrorBox && otpErrorsList) {
              otpErrorsList.innerHTML = '';
              const li = document.createElement('li');
              li.textContent = data.message || 'Failed to send OTP. Please try again.';
              otpErrorsList.appendChild(li);
              otpErrorBox.style.display = 'flex';
            }
          } else {
            // success
            if (otpSuccessMsg && otpSuccessBox) {
              otpSuccessMsg.textContent = data.message || 'OTP has been sent to the Masters Admin.';
              otpSuccessBox.style.display = 'flex';
            }
            if (otpFlag) otpFlag.value = '1';
            if (otpInput) otpInput.focus();
          }
        }).catch(() => {
          if (otpErrorBox && otpErrorsList) {
            otpErrorsList.innerHTML = '';
            const li = document.createElement('li');
            li.textContent = 'Network error while sending OTP. Please try again.';
            otpErrorsList.appendChild(li);
            otpErrorBox.style.display = 'flex';
          }
        }).finally(() => {
          sendOtpBtn.disabled = false;
          sendOtpBtn.textContent = 'Send OTP';
        });
      });
    }

    // FINAL SUBMIT: prevent submitting if OTP was never requested
    if (form) {
      form.addEventListener('submit', function (e) {
        if (otpFlag && otpFlag.value !== '1') {
          e.preventDefault();
          if (otpErrorBox && otpErrorsList) {
            otpErrorsList.innerHTML = '';
            const li = document.createElement('li');
            li.textContent = 'Please send an OTP first using the "Send OTP" button.';
            otpErrorsList.appendChild(li);
            otpErrorBox.style.display = 'flex';
          }
          return false;
        }
      });
    }
  })();
</script>
@endpush
