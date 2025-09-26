@extends('layout.mainlayout')

@section('title', 'Register · GenRev')

@section('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inria+Sans:wght@400;600;700&family=Kalam:wght@400;700&display=swap');

  /* ------ Brand tokens (same set as Login) ------ */
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

  /* ------- Full-screen overlay but scrollable ------- */
  .auth-wrap{
    position:fixed; inset:0; z-index:1000;
    overflow:auto;                       /* <— allows page scroll when needed */
    background:
      radial-gradient(1200px 800px at 10% -10%, rgba(220,38,38,.06), transparent 60%),
      radial-gradient(900px 700px at 110% 110%, rgba(22,163,74,.08), transparent 60%),
      var(--bg-offwhite);
    color:var(--ink);
    font-family:'Inria Sans',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
  }
  /* centers content on tall screens; still scrolls when short */
  .auth-center{
    min-height:100vh;
    display:flex; align-items:center; justify-content:center;
    padding:2.5rem 1rem;
  }

  /* ------- Card (same visual language as Login) ------- */
  .card{
    width:100%;
    max-width:560px;
    background:#fff;
    border:1px solid var(--line);
    border-radius:1.25rem;
    box-shadow:0 1px 2px rgba(0,0,0,.04),0 12px 28px rgba(0,0,0,.06);
    padding:1.85rem;
  }

  .title{font-family:'Kalam',cursive;font-size:1.75rem;line-height:1.2;margin:0 0 .25rem;text-align:center}
  .underline{display:inline-block;position:relative}
  .underline::after{content:'';position:absolute;left:0;right:0;bottom:-4px;height:4px;border-radius:999px;background:linear-gradient(90deg,var(--red) 0%,var(--green) 60%,var(--blue) 100%);opacity:.25}
  .sub{color:var(--muted);font-size:.9rem;text-align:center;margin:.35rem 0 1rem}

  .alert{border:1px solid;border-radius:.9rem;padding:.65rem .8rem;display:flex;justify-content:space-between;gap:.75rem;align-items:center;margin-bottom:1rem}
  .alert-success{background:var(--green-50);border-color:#86efac;color:#065f46}
  .alert-error{background:var(--red-50);border-color:#fca5a5;color:#7f1d1d}
  .close{background:transparent;border:0;font-size:1.15rem;line-height:1;cursor:pointer;opacity:.7}.close:hover{opacity:1}

  .label{font-size:.9rem;color:var(--muted);margin-bottom:.4rem;display:block}
  .input{
    width:100%;background:#fff;color:var(--ink);
    border:1px solid var(--line);border-radius:.8rem;
    padding:.65rem .85rem;line-height:1.4;
    transition:box-shadow .15s ease,border-color .15s ease;
  }
  .input::placeholder{color:#94a3b8}
  .input:focus{outline:0;border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
  .input[aria-invalid="true"]{border-color:var(--red-600);box-shadow:0 0 0 3px rgba(220,38,38,.12)}
  .row{margin-bottom:1rem}
  .help{font-size:.75rem;color:var(--muted);margin-top:.35rem}

  .field{position:relative}
  .toggle-eye{position:absolute;right:.75rem;top:50%;transform:translateY(-50%);width:20px;height:20px;color:#64748b;cursor:pointer;opacity:.8}
  .toggle-eye:hover{opacity:1}

  .btn{display:inline-flex;justify-content:center;align-items:center;gap:.5rem;width:100%;
    font-weight:700;border-radius:.8rem;padding:.7rem 1rem;border:1px solid transparent;
    background:var(--red);color:#fff;transition:transform .12s ease,filter .15s ease}
  .btn:hover{filter:brightness(.97)}
  .btn[disabled]{opacity:.6;cursor:not-allowed}

  .link{color:var(--blue);font-weight:600;text-decoration:none}
  .link:hover{text-decoration:underline}

  .meter{height:6px;border-radius:999px;background:#eef2ff;overflow:hidden;margin-top:.5rem}
  .meter>i{display:block;height:100%;width:0;background:linear-gradient(90deg,#ef4444,#f59e0b,#22c55e);transition:width .2s ease}

  @media (prefers-reduced-motion:no-preference){
    .fade{animation:fade .2s ease both}
    @keyframes fade{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
  }
  @media (max-width:480px){ .card{max-width:420px;padding:1.25rem} }
  @media (min-width:1280px){ .card{max-width:600px} }
</style>
@endsection

@section('content')
<div class="auth-wrap">
  <div class="auth-center">
    <div class="card fade" role="region" aria-label="Registration form">

      <h2 class="title"><span class="underline">Register New Account</span></h2>
      <p class="sub">Create your GenRev access. You can update details later in Settings.</p>

      {{-- Flash messages --}}
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

      {{-- Validation summary --}}
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

      <form method="POST" action="{{ route('register.submit') }}" id="regForm" novalidate>
        @csrf

        {{-- Full name --}}
        <div class="row">
          <label for="name" class="label">Full Name</label>
          <input
            type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
            autocomplete="name" placeholder="Jane D. Santos" class="input"
            aria-invalid="@error('name') true @else false @enderror">
          @error('name') <p class="help" style="color:#b91c1c">{{ $message }}</p> @enderror
        </div>

        {{-- Username --}}
        <div class="row">
          <label for="username" class="label">Username</label>
          <input
            type="text" id="username" name="username" value="{{ old('username') }}"
            placeholder="jane.santos" class="input"
            aria-invalid="@error('username') true @else false @enderror">
          <p class="help">Leave blank to use your email as the username.</p>
          @error('username') <p class="help" style="color:#b91c1c">{{ $message }}</p> @enderror
        </div>

        {{-- Email --}}
        <div class="row">
          <label for="email" class="label">Email Address</label>
          <input
            type="email" id="email" name="email" value="{{ old('email') }}" required
            autocomplete="email" placeholder="you@domain.com" class="input"
            aria-invalid="@error('email') true @else false @enderror">
          @error('email') <p class="help" style="color:#b91c1c">{{ $message }}</p> @enderror
        </div>

        {{-- Password --}}
        <div class="row">
          <label for="password" class="label">Password</label>
          <div class="field">
            <input
              type="password" id="password" name="password" required
              autocomplete="new-password" placeholder="••••••••" class="input pr-10"
              aria-invalid="@error('password') true @else false @enderror">
            <svg id="togglePass" class="toggle-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" role="button" tabindex="0" aria-label="Toggle password visibility"><path d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/></svg>
          </div>
          <div class="meter" aria-hidden="true"><i id="pwBar"></i></div>
          <p id="pwHint" class="help">Use at least 8 characters with a mix of letters and numbers.</p>
          @error('password') <p class="help" style="color:#b91c1c">{{ $message }}</p> @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="row">
          <label for="password_confirmation" class="label">Confirm Password</label>
          <div class="field">
            <input
              type="password" id="password_confirmation" name="password_confirmation" required
              autocomplete="new-password" placeholder="Repeat password" class="input pr-10">
            <svg id="togglePass2" class="toggle-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" role="button" tabindex="0" aria-label="Toggle password visibility"><path d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/></svg>
          </div>
          <p id="matchHint" class="help">Passwords must match.</p>
        </div>

        {{-- Position (optional) --}}
        <div class="row">
          <label for="position" class="label">Position <span style="opacity:.7">(optional)</span></label>
          <input
            type="text" id="position" name="position" value="{{ old('position') }}"
            placeholder="e.g., Inventory Associate" class="input"
            aria-invalid="@error('position') true @else false @enderror">
          @error('position') <p class="help" style="color:#b91c1c">{{ $message }}</p> @enderror
        </div>

        {{-- Role --}}
        <div class="row">
          <label for="role" class="label">User Role</label>
          <select id="role" name="role" required class="input"
                  aria-invalid="@error('role') true @else false @enderror">
            <option value="admin"     {{ old('role')==='admin'?'selected':'' }}>Admin</option>
            <option value="sales"     {{ old('role')==='sales'?'selected':'' }}>Sales</option>
            <option value="inventory" {{ old('role')==='inventory'?'selected':'' }}>Inventory</option>
          </select>
          @error('role') <p class="help" style="color:#b91c1c">{{ $message }}</p> @enderror
        </div>

        <button type="submit" id="submitBtn" class="btn">Create Account</button>

        <p class="sub" style="margin-top:.9rem">
          Already have an account?
          <a href="{{ route('login') }}" class="link">Login here</a>
        </p>

        {{-- honeypot --}}
        <input type="text" name="website_url" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0;">
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function(){
    const $ = (s,root=document)=>root.querySelector(s);
    const email = $('#email'), uname = $('#username');
    const p1 = $('#password'), p2 = $('#password_confirmation');
    const bar = $('#pwBar'), hint = $('#pwHint'), matchHint = $('#matchHint');
    const t1 = $('#togglePass'), t2 = $('#togglePass2');
    const btn = $('#submitBtn'), form = $('#regForm');

    // suggest username from email if blank
    if (email && uname) {
      email.addEventListener('blur', () => {
        if (!uname.value.trim() && email.value.includes('@')) {
          uname.value = email.value.split('@')[0].replace(/[^a-zA-Z0-9._-]/g,'');
        }
      });
    }

    // strength & match
    function score(v){ let s=0; if(v.length>=8)s+=30; if(/[A-Z]/.test(v))s+=20; if(/[a-z]/.test(v))s+=20; if(/[0-9]/.test(v))s+=15; if(/[^A-Za-z0-9]/.test(v))s+=15; return Math.min(100,s); }
    function draw(){ const v=p1.value||''; const s=score(v); if(bar) bar.style.width=(s||5)+'%';
      if(hint){ hint.textContent = s<40?'Weak — add length and variety.':(s<70?'Medium — add more variety.':'Strong password.'); } }
    function match(){ if(!p2.value){ matchHint.textContent='Passwords must match.'; matchHint.style.color=''; return; }
      const ok = p1.value && p1.value===p2.value; matchHint.textContent= ok?'Passwords match.':'Passwords do not match.'; matchHint.style.color= ok?'#16a34a':'#b91c1c'; }
    p1 && p1.addEventListener('input', ()=>{ draw(); match(); });
    p2 && p2.addEventListener('input', match); draw();

    // show/hide
    const toggler=(icon,input)=>{ if(!icon||!input)return; const open='M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10z', closed='M2 5.27L3.28 4 20 20.72 18.73 22l-3.08-3.08A12.8 12.8 0 0112 22C5 22 1 15 1 15s1.7-3.12 4.93-5.56L2 5.27zM12 6c6.73 0 11 7 11 7a24.83 24.83 0 01-4.09 5.16l-2.1-2.1A9.7 9.7 0 0022 13s-4.27-7-10-7c-1.07 0-2.09.17-3.04.49L7.32 5.85A12.2 12.2 0 0112 6z'; const path=icon.querySelector('path'); const swap=()=>{ const t=input.type==='password'; input.type=t?'text':'password'; if(path) path.setAttribute('d', t?closed:open); }; icon.addEventListener('click',swap); icon.addEventListener('keydown',e=>{ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); swap(); }}); };
    toggler(t1,p1); toggler(t2,p2);

    // submit state
    const regForm = document.getElementById('regForm');
    if (regForm && btn) {
      regForm.addEventListener('submit', function(){ btn.setAttribute('disabled','disabled'); btn.textContent='Creating account…'; });
    }
  })();
</script>
@endpush
