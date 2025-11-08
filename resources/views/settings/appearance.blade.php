@extends('layout.mainlayout')

@section('title', 'Appearance • GenRev Admin Dashboard')

@section('head')
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --line:#e5e7eb; --muted:#6b7280; --ink:#111827; --card:#ffffff;
      --blue:#3b82f6; --green:#22c55e; --purple:#8b5cf6; --orange:#f59e0b; --red:#ef4444;
    }
    .settings-modal{ background:#fff; border:1px solid var(--line); border-radius: 1.25rem; box-shadow: 0 20px 60px rgba(0,0,0,.12); }
    .side-item{ display:flex; align-items:center; gap:.6rem; padding:.55rem .65rem; border-radius:.6rem; font-weight:500; color:#374151; }
    .side-item.active{ background:#f3f4f6; }
    .section-title{ font-weight:700; font-size:1rem; }
    .sub{ color: var(--muted); font-size:.85rem; }
    .hr{ height:1px; background: var(--line); }
    .theme-grid{ display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:.75rem; }
    .theme-card{ position:relative; border:1px solid var(--line); border-radius: .9rem; padding:.7rem; cursor:pointer; }
    .theme-card[aria-checked="true"]{ outline:2px solid var(--blue); box-shadow: 0 0 0 4px rgba(59,130,246,.12); }
    .theme-thumb{ border-radius:.6rem; height:110px; display:grid; grid-template-rows:auto 1fr; overflow:hidden; }
    .thumb-bar{ height:12px; display:flex; align-items:center; gap:4px; padding:0 6px; }
    .dot{ width:6px; height:6px; border-radius:999px; background:#e5e7eb; }
    .thumb-body{ display:grid; align-content:start; gap:6px; padding:8px; }
    .line{ height:6px; border-radius:999px; background: rgba(0,0,0,.08); }
    .dark .line{ background: rgba(255,255,255,.18); }
    .check{ position:absolute; bottom:8px; right:10px; width:22px; height:22px; border-radius:999px; background:#2563eb; color:#fff; display:grid; place-items:center; font-size:12px; box-shadow:0 2px 6px rgba(0,0,0,.25); opacity:0; transform: scale(.9); transition:.15s; }
    .theme-card[aria-checked="true"] .check{ opacity:1; transform: scale(1); }
    .accent-dot{ width:26px; height:26px; border-radius:999px; border:2px solid #fff; box-shadow: 0 0 0 1px var(--line), 0 2px 6px rgba(0,0,0,.06); cursor:pointer; }
    .accent-dot[aria-checked="true"]{ outline:2px solid #2563eb; outline-offset:2px; }
    .font-pill{ display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .5rem; border-radius:.6rem; border:1px solid var(--line); background:#fafafa; cursor:pointer; }
    .font-pill[aria-checked="true"]{ border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
    .font-swatch{ width:28px; height:28px; border-radius:.5rem; display:grid; place-items:center; font-weight:700; }
    .preview-mini{ border:1px solid var(--line); border-radius:.9rem; }
  </style>
@endsection

@section('content')
<div class="p-6">
  <div class="settings-modal mx-auto max-w-5xl grid grid-cols-12 overflow-hidden">
    {{-- Sidebar --}}
    <aside class="col-span-4 md:col-span-3 p-4 bg-white">
      <div class="text-sm font-semibold mb-2 text-gray-500">Settings</div>
      <nav class="space-y-1">
        <a href="{{ route('settings.account') }}" class="side-item"><span>👤</span><span>Account</span></a>
        <div class="side-item active"><span>🎨</span><span>Appearance</span></div>
        <a href="{{ route('notifications.index') }}" class="side-item"><span>🔔</span><span>Notifications</span></a>
      </nav>
    </aside>

    {{-- Main --}}
    <section class="col-span-8 md:col-span-9 p-6 bg-white">
      <h2 class="section-title">Appearance</h2>
      <div class="hr my-4"></div>

      {{-- Theme --}}
      <div class="mb-6">
        <div class="flex items-baseline justify-between">
          <div>
            <div class="section-title">Theme</div>
            <div class="sub">Customize your UI theme</div>
          </div>
        </div>

        @php $theme = old('theme', $appearance['theme'] ?? 'light'); @endphp
        <div class="theme-grid mt-3">
          <label class="theme-card" role="radio" aria-checked="{{ $theme==='light'?'true':'false' }}">
            <input type="radio" name="theme" value="light" class="sr-only" {{ $theme==='light'?'checked':'' }} data-theme="light">
            <div class="theme-thumb bg-white">
              <div class="thumb-bar">
                <span class="dot" style="background:#ef4444"></span>
                <span class="dot" style="background:#f59e0b"></span>
                <span class="dot" style="background:#10b981"></span>
              </div>
              <div class="thumb-body">
                <div class="line" style="width:70%"></div>
                <div class="line" style="width:40%"></div>
                <div class="line" style="width:85%"></div>
              </div>
            </div>
            <div class="check">✓</div>
            <div class="mt-2 text-sm text-center">Light</div>
          </label>

          <label class="theme-card" role="radio" aria-checked="{{ $theme==='dark'?'true':'false' }}">
            <input type="radio" name="theme" value="dark" class="sr-only" {{ $theme==='dark'?'checked':'' }} data-theme="dark">
            <div class="theme-thumb bg-slate-900 text-white dark">
              <div class="thumb-bar">
                <span class="dot" style="background:#475569"></span>
                <span class="dot" style="background:#64748b"></span>
                <span class="dot" style="background:#94a3b8"></span>
              </div>
              <div class="thumb-body">
                <div class="line" style="width:70%"></div>
                <div class="line" style="width:40%"></div>
                <div class="line" style="width:85%"></div>
              </div>
            </div>
            <div class="check">✓</div>
            <div class="mt-2 text-sm text-center">Dark</div>
          </label>

          <label class="theme-card" role="radio" aria-checked="{{ $theme==='system'?'true':'false' }}">
            <input type="radio" name="theme" value="system" class="sr-only" {{ $theme==='system'?'checked':'' }} data-theme="system">
            <div class="theme-thumb">
              <div class="thumb-bar" style="background:#0f172a"></div>
              <div class="grid grid-cols-2">
                <div class="thumb-body bg-white">
                  <div class="line" style="width:65%"></div>
                  <div class="line" style="width:35%"></div>
                </div>
                <div class="thumb-body bg-slate-900 text-white dark">
                  <div class="line" style="width:65%"></div>
                  <div class="line" style="width:35%"></div>
                </div>
              </div>
            </div>
            <div class="check">✓</div>
            <div class="mt-2 text-sm text-center">System</div>
          </label>
        </div>
      </div>

      <div class="hr my-5"></div>

      {{-- Accent color --}}
      @php $accent = old('accent', $appearance['accent'] ?? '#3b82f6'); @endphp
      <div class="mb-6">
        <div class="section-title">Accent color</div>
        <div class="sub mb-3">Choose your accent color</div>
        <div class="flex items-center gap-3">
          @foreach([["#3b82f6","blue"],["#22c55e","green"],["#8b5cf6","purple"],["#f59e0b","orange"],["#ef4444","red"]] as [$hex,$name])
            <label class="accent-dot" title="{{ ucfirst($name) }}" style="background: {{ $hex }}" aria-checked="{{ $accent===$hex?'true':'false' }}">
              <input type="radio" class="sr-only" name="accent" value="{{ $hex }}" {{ $accent===$hex?'checked':'' }} data-accent="{{ $hex }}">
            </label>
          @endforeach
        </div>
      </div>

      <div class="hr my-5"></div>

      {{-- Font style --}}
      @php $font = old('font_style', $appearance['font_style'] ?? 'default'); @endphp
      <div class="mb-2">
        <div class="section-title">Font style</div>
        <div class="sub mb-3">Choose your font style</div>
        <div class="flex items-center gap-2">
          <label class="font-pill" aria-checked="{{ $font==='default'?'true':'false' }}">
            <input type="radio" name="font_style" value="default" class="sr-only" {{ $font==='default'?'checked':'' }}>
            <span class="font-swatch" style="font-family: Inter">Ag</span>
            <span class="text-sm">Default</span>
          </label>
          <label class="font-pill" aria-checked="{{ $font==='rounded'?'true':'false' }}">
            <input type="radio" name="font_style" value="rounded" class="sr-only" {{ $font==='rounded'?'checked':'' }}>
            <span class="font-swatch" style="font-family: Jost">Ag</span>
            <span class="text-sm">Rounded</span>
          </label>
          <label class="font-pill" aria-checked="{{ $font==='mono'?'true':'false' }}">
            <input type="radio" name="font_style" value="mono" class="sr-only" {{ $font==='mono'?'checked':'' }}>
            <span class="font-swatch" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace">Ag</span>
            <span class="text-sm">Mono</span>
          </label>
        </div>
      </div>

      {{-- Mini preview strip --}}
      <div class="preview-mini mt-5 p-3 rounded-lg">
        <div class="flex items-center justify-between text-sm">
          <div class="flex items-center gap-2">
            <div class="w-2.5 h-2.5 rounded-full" id="miniAccent" style="background: {{ $accent }}"></div>
            <span class="text-gray-600">This is a tiny preview strip</span>
          </div>
          <a href="{{ route('dashboard') }}" class="text-blue-600 font-semibold">Open Dashboard</a>
        </div>
      </div>

      {{-- Footer actions --}}
      <div class="mt-6 flex items-center justify-end gap-2">
        <a href="{{ route('settings.appearance.reset') }}" class="px-3 py-2 text-sm rounded-lg border">Reset</a>
        <form method="POST" action="{{ route('settings.appearance.update') }}" class="inline">
          @csrf
          <input type="hidden" name="theme" id="formTheme" value="{{ $theme }}">
          <input type="hidden" name="accent" id="formAccent" value="{{ $accent }}">
          <input type="hidden" name="font_style" id="formFont" value="{{ $font }}">
          <button type="submit" class="px-3 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white">Save Changes</button>
        </form>
      </div>
    </section>
  </div>
</div>
@endsection

@push('head')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Theme cards
    const themeCards = document.querySelectorAll('.theme-card');
    const formTheme = document.getElementById('formTheme');
    themeCards.forEach(card => {
      card.addEventListener('click', () => {
        themeCards.forEach(c => c.setAttribute('aria-checked','false'));
        card.setAttribute('aria-checked','true');
        const input = card.querySelector('input[type="radio"][name="theme"]');
        if(input){ input.checked = true; formTheme.value = input.value; }
      });
    });

    // Accent dots
    const accentDots = document.querySelectorAll('.accent-dot');
    const formAccent = document.getElementById('formAccent');
    const miniAccent = document.getElementById('miniAccent');
    accentDots.forEach(dot => {
      dot.addEventListener('click', () => {
        accentDots.forEach(d => d.setAttribute('aria-checked','false'));
        dot.setAttribute('aria-checked','true');
        const input = dot.querySelector('input');
        if(input){ input.checked = true; formAccent.value = input.value; miniAccent.style.background = input.value; }
      });
    });

    // Font style pills
    const fontPills = document.querySelectorAll('.font-pill');
    const formFont = document.getElementById('formFont');
    fontPills.forEach(pill => {
      pill.addEventListener('click', () => {
        fontPills.forEach(p => p.setAttribute('aria-checked','false'));
        pill.setAttribute('aria-checked','true');
        const input = pill.querySelector('input');
        if(input){ input.checked = true; formFont.value = input.value; }
      });
    });
  });
</script>
@endpush
