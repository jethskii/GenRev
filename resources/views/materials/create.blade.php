{{-- Add Material (Liquid / Gooey UI) - Upgraded Modal --}}
@extends('layout.mainlayout')

@section('content')
@php
  $categoryCatalog = [
    'Primary Raw Materials','Meat Cuts & Trimmings','Fats / Skins','Salt',
    'Curing Agents (Nitrite/Nitrate)','Phosphates','Spices & Seasonings',
    'Fillers & Binders','Sugars','Water / Ice','Smoke Materials','Casings',
    'Packaging Films & Bags','Labels & Cartons','Cleaning & Sanitation (Non-food)',
  ];
  $unitOptions = [
    'kg'=>'Kilograms','g'=>'Grams','lbs'=>'Pounds','pcs'=>'Pieces','pkg'=>'Package',
    'box'=>'Box','bag'=>'Bag','roll'=>'Roll','tray'=>'Tray','lt'=>'Liters','ml'=>'Milliliters','m3'=>'Cubic Meter',
  ];
  $oldUnit = old('unit', 'kg'); // default to kg
@endphp

{{-- Invisible SVG filter for gooey effect --}}
<svg class="absolute w-0 h-0">
  <defs>
    <filter id="gooey">
      <feGaussianBlur in="SourceGraphic" stdDeviation="7" result="blur"/>
      <feColorMatrix in="blur" mode="matrix"
        values="1 0 0 0 0  
                0 1 0 0 0  
                0 0 1 0 0  
                0 0 0 22 -10"
        result="goo"/>
      <feBlend in="SourceGraphic" in2="goo"/>
    </filter>
  </defs>
</svg>

<div
  x-data="materialModal({ visibleInitially: {{ $errors->any() ? 'true' : 'false' }} })"
  x-id="['modal-title']"
  class="relative overflow-hidden rounded-2xl border border-emerald-500/15 bg-gradient-to-br from-[#020617] via-[#020c10] to-[#020617] text-white shadow-[0_18px_60px_rgba(0,0,0,.75)]"
>
  {{-- Soft animated background grid / glow --}}
  <div class="pointer-events-none absolute inset-0 opacity-60 mix-blend-screen">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_0_0,#22c55e22,transparent_55%),radial-gradient(circle_at_100%_100%,#38bdf822,transparent_55%)] animate-slow-pan"></div>
    <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(148,163,184,.14)_1px,transparent_1px),linear-gradient(to_bottom,rgba(148,163,184,.09)_1px,transparent_1px)] bg-[size:60px_60px] opacity-40"></div>
  </div>

  <div class="relative p-6 lg:p-7">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-4 md:mb-6">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/5 px-3 py-1 text-[11px] uppercase tracking-[.18em] text-emerald-300">
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
          <span>Material Library</span>
        </div>
        <h2 class="mt-2 text-2xl md:text-3xl font-bold tracking-tight">
          Raw Materials
        </h2>
        <p class="mt-1 max-w-xl text-sm text-slate-400">
          Centralized list of ingredients, additives, and packaging inputs that fuel your meat production workflow.
        </p>
      </div>

      {{-- Header stats + Trigger --}}
      <div class="flex flex-col items-end gap-3">
        <div class="flex items-center gap-3">
          <div class="rounded-2xl border border-slate-700/80 bg-slate-900/60 px-3 py-2 text-right shadow-inner">
            <p class="text-[11px] uppercase tracking-[.16em] text-slate-400">Health</p>
            <div class="mt-1 flex items-center gap-2">
              <div class="relative h-7 w-7">
                <svg class="h-7 w-7 text-emerald-400/60" viewBox="0 0 36 36">
                  <path
                    class="text-slate-700"
                    d="M18 2 a16 16 0 1 1 0 32 a16 16 0 1 1 0 -32"
                    fill="none" stroke="currentColor" stroke-width="3"
                    stroke-linecap="round" stroke-dasharray="100" stroke-dashoffset="0" />
                  <path
                    d="M18 2 a16 16 0 1 1 0 32 a16 16 0 1 1 0 -32"
                    fill="none" stroke="currentColor" stroke-width="3"
                    stroke-linecap="round" stroke-dasharray="66" stroke-dashoffset="0" />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                  <span class="text-[10px] font-semibold text-emerald-300">66%</span>
                </div>
              </div>
              <div>
                <p class="text-xs text-slate-400">Stock coverage</p>
                <p class="text-sm font-semibold text-slate-50">Balanced</p>
              </div>
            </div>
          </div>

          {{-- Trigger to open modal --}}
          <button
            @click="open()"
            type="button"
            class="group relative inline-flex items-center gap-2 overflow-hidden rounded-xl border border-emerald-300/30 bg-emerald-400/10 px-4 py-2 text-sm font-semibold text-emerald-100 shadow-[0_0_24px_rgba(16,185,129,.35)] transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-300/60 hover:bg-emerald-400/20"
          >
            <span class="absolute inset-0 bg-[radial-gradient(circle_at_0_0,rgba(74,222,128,.35),transparent_55%),radial-gradient(circle_at_100%_100%,rgba(45,212,191,.35),transparent_55%)] opacity-0 transition-opacity group-hover:opacity-100"></span>
            <span class="relative flex h-5 w-5 items-center justify-center rounded-lg bg-emerald-500/20 text-emerald-300 text-xs">+</span>
            <span class="relative">Add Material</span>
          </button>
        </div>

        <div class="flex items-center gap-2 text-[11px] text-slate-400">
          <span class="inline-flex h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
          <span>Low stock alerts will trigger once thresholds are set.</span>
        </div>
      </div>
    </div>

    {{-- Placeholder list area --}}
    <div class="relative mt-2 rounded-2xl border border-slate-800/80 bg-slate-900/60 px-4 py-5 text-sm text-slate-400 shadow-inner">
      <div class="pointer-events-none absolute inset-0 rounded-2xl bg-gradient-to-tr from-slate-50/[0.03] via-transparent to-emerald-500/[0.05]"></div>
      <div class="relative flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="flex h-9 w-9 items-center justify-center rounded-2xl bg-slate-950/80 ring-1 ring-slate-700/90">
            <svg class="h-4 w-4 text-slate-300" viewBox="0 0 24 24" fill="none">
              <path d="M4 7h16v11H4z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M7 7V6a5 5 0 0 1 10 0v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>
          <div>
            <p class="text-xs font-semibold text-slate-200 tracking-wide">No materials yet</p>
            <p class="text-[11px] text-slate-400">
              Start by adding your first raw material to unlock usage tracking, low stock alerts, and material level analytics.
            </p>
          </div>
        </div>
        <div class="hidden md:flex items-center gap-2 text-[11px] text-slate-400">
          <span class="rounded-full bg-slate-800/80 px-2 py-1">Tip: Use the Auto SKU tool for consistent naming.</span>
        </div>
      </div>
    </div>
  </div>

  {{-- Animated Modal --}}
  <template x-teleport="body">
    <div
      x-show="visible"
      x-transition.opacity.duration.200ms
      class="fixed inset-0 z-[60] bg-black/60 backdrop-blur-md"
      @keydown.window.escape="close()"
      aria-modal="true" role="dialog" :aria-labelledby="$id('modal-title')"
    >
      {{-- Click outside to close --}}
      <div class="absolute inset-0" @click.self="close()"></div>

      <div
        x-show="visible"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0 translate-y-3 scale-[.97]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 scale-[.97]"
        class="mx-auto mt-10 w-[95%] max-w-3xl rounded-3xl border border-emerald-400/25 bg-slate-950/95 shadow-[0_30px_80px_rgba(0,0,0,.9)] relative overflow-hidden"
        @click.stop
      >
        {{-- Glow frame --}}
        <div class="pointer-events-none absolute inset-0 rounded-3xl bg-[radial-gradient(circle_at_0_0,rgba(34,197,94,.35),transparent_55%),radial-gradient(circle_at_100%_100%,rgba(45,212,191,.35),transparent_55%)] opacity-40 mix-blend-screen"></div>

        <div class="relative p-6 md:p-8">
          <div class="flex items-start justify-between mb-6 gap-4">
            <div>
              <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-3 py-1 text-[11px] font-medium text-emerald-300 ring-1 ring-emerald-400/30">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-300 animate-ping"></span>
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                <span>New Material</span>
              </div>
              <h3 :id="$id('modal-title')" class="mt-2 text-xl md:text-2xl font-semibold text-emerald-100 drop-shadow">
                Add Material
              </h3>
              <p class="mt-1 text-xs md:text-sm text-slate-400">
                Define units, pricing, and stock thresholds so production never stalls due to missing ingredients.
              </p>
            </div>
            <button
              @click="close()"
              class="group flex h-9 w-9 items-center justify-center rounded-2xl border border-slate-700/70 bg-slate-900/70 text-slate-400 text-2xl leading-none shadow-sm transition hover:border-red-400/60 hover:text-red-300 hover:bg-red-950/60"
            >
              <span class="relative -mt-0.5">&times;</span>
            </button>
          </div>

          {{-- Errors --}}
          @if($errors->any())
            <div class="mb-4 rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-xs text-red-200">
              <div class="flex items-start gap-2">
                <span class="mt-[2px] inline-flex h-4 w-4 items-center justify-center rounded-full bg-red-500/80 text-[10px] font-bold text-white">!</span>
                <ul class="list-disc pl-4 space-y-1">
                  @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                  @endforeach
                </ul>
              </div>
            </div>
          @endif

          <form
            id="materialForm"
            method="POST"
            action="{{ route('materials.store') }}"
            class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6"
          >
            @csrf

            {{-- Section: Identity --}}
            <div class="md:col-span-2">
              <div class="mb-2 flex items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-xl bg-slate-900/80 ring-1 ring-slate-700/80">
                  <svg class="h-3.5 w-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none">
                    <path d="M5 20.5V5.5L12 3l7 2.5v15L12 18l-7 2.5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                    <path d="M12 18V3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                  </svg>
                </span>
                <div>
                  <p class="text-xs font-semibold text-slate-200 tracking-wide">Material identity</p>
                  <p class="text-[11px] text-slate-500">Give this material a clear, production-ready name.</p>
                </div>
              </div>
              <div class="relative">
                <label class="label-dark">Name</label>
                <input
                  name="material_name"
                  value="{{ old('material_name') }}"
                  required
                  class="input-dark w-full"
                  placeholder="e.g., Pork Lean, Prague Powder #1, Vacuum Bag 300x400mm"
                />
                <span class="pointer-events-none absolute right-3 top-8 text-[10px] text-slate-500">Required</span>
              </div>
            </div>

            {{-- Section: Category --}}
            <div class="relative">
              <label class="label-dark">Category</label>
              <select name="category" class="input-dark w-full">
                <option value="">Select category</option>
                @foreach($categoryCatalog as $_c)
                  <option value="{{ $_c }}" @selected(old('category') === $_c)>{{ $_c }}</option>
                @endforeach
              </select>

              {{-- Quick-select chips --}}
              <div class="mt-2 flex flex-wrap gap-2">
                @foreach($categoryCatalog as $_c)
                  <button
                    type="button"
                    data-chip-category="{{ $_c }}"
                    class="category-chip px-2.5 py-1 rounded-full text-[11px] border border-white/10 bg-white/5 text-slate-200/80 hover:bg-emerald-500/10 hover:border-emerald-400/40 hover:text-emerald-100 transition-colors"
                  >
                    {{ $_c }}
                  </button>
                @endforeach
              </div>
            </div>

            {{-- Section: Unit --}}
            <div>
              <label class="label-dark">Unit of Measure</label>
              <div class="relative">
                <select name="unit" class="input-dark w-full" required>
                  @foreach($unitOptions as $v => $label)
                    <option value="{{ $v }}" @selected($oldUnit === $v)>{{ $label }}</option>
                  @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-[10px] text-slate-500">
                  Unit
                </div>
              </div>
            </div>

            {{-- Section: Pricing --}}
            <div>
              <label class="label-dark">Unit Price (₱)</label>
              <div class="relative">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">₱</span>
                <input
                  name="unit_price"
                  type="number"
                  min="0" step="0.01"
                  class="input-dark w-full pl-7"
                  value="{{ old('unit_price', 0) }}"
                  required
                />
              </div>
              <p class="mt-1 text-[11px] text-slate-500">Per selected unit (kg, pcs, etc.).</p>
            </div>

            {{-- Section: Quantity --}}
            <div>
              <label class="label-dark">Quantity (kg)</label>
              <input
                name="quantity_kg"
                type="number"
                min="0"
                step="0.001"
                class="input-dark w-full"
                value="{{ old('quantity_kg', 0) }}"
                required
              />
              <p class="mt-1 text-[11px] text-slate-500">Initial on-hand quantity for this material.</p>
            </div>

            {{-- Section: Threshold --}}
            <div>
              <label class="label-dark">
                Min Stock (kg)
                <span class="text-white/40 text-[10px]">(for low-stock alerts)</span>
              </label>
              <input
                name="min_stock_kg"
                type="number"
                min="0"
                step="0.001"
                class="input-dark w-full"
                value="{{ old('min_stock_kg') }}"
              />
              <p class="mt-1 text-[11px] text-slate-500">Alerts trigger when stock drops below this level.</p>
            </div>

            {{-- Section: SKU --}}
            <div class="md:col-span-2">
              <div class="mb-1 flex items-center justify-between">
                <label class="label-dark mb-0">SKU (optional)</label>
                <span class="text-[10px] text-slate-500">Make it human readable and searchable.</span>
              </div>
              <div class="flex flex-col gap-2 sm:flex-row">
                <div class="relative flex-1">
                  <input
                    id="skuInput"
                    name="sku"
                    class="input-dark w-full font-mono text-xs"
                    placeholder="e.g., MT-PORK-LEAN"
                    value="{{ old('sku') }}"
                  />
                  <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-500">Optional</span>
                </div>
                <button
                  type="button"
                  id="skuGen"
                  class="inline-flex items-center justify-center gap-1 rounded-xl border border-emerald-300/30 bg-emerald-400/10 px-3 py-2 text-xs font-medium text-emerald-100 hover:bg-emerald-400/20 hover:border-emerald-300/60 transition"
                >
                  <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
                    <path d="M5 5h6v6H5zM13 5h6v6h-6zM5 13h6v6H5zM13 15h6v4h-6z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                  </svg>
                  <span>Auto SKU</span>
                </button>
              </div>
              <p class="text-[11px] text-slate-500 mt-1">
                Tip: Use a readable pattern like <code>CAT-ITEM-VARIANT</code> (e.g., <code>PKG-VACBAG-300x400</code>).
              </p>
            </div>

            {{-- Action row --}}
            <div class="md:col-span-2 mt-2 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div class="flex items-center gap-2 text-[11px] text-slate-500">
                <span class="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                <span>All fields can be edited later from the material detail view.</span>
              </div>

              <div class="flex items-center justify-end gap-3">
                <button
                  type="button"
                  @click="close()"
                  class="px-4 py-2 rounded-xl border border-slate-700/80 bg-slate-900/80 text-sm text-slate-200 hover:bg-slate-800 transition"
                >
                  Cancel
                </button>

                <button type="submit" id="btnSave" class="liquid-btn relative group">
                  <span class="label" data-label-default="Save Material">Save Material</span>
                  <span class="blob blob-1"></span>
                  <span class="blob blob-2"></span>
                  <span class="blob blob-3"></span>
                  <span class="blob blob-4"></span>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>
</div>

{{-- local styles for inputs + gooey button + effects --}}
<style>
  .label-dark{
    @apply text-xs text-gray-400 mb-1 block;
  }

  .input-dark{
    @apply px-3 py-2.5 rounded-xl w-full text-gray-100 outline-none border text-sm;
    background: radial-gradient(circle at 0 0,rgba(34,197,94,.12),transparent 55%),
                radial-gradient(circle at 100% 100%,rgba(56,189,248,.12),transparent 55%),
                rgba(15,23,42,.95);
    border-color: rgba(148,163,184,.35);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.02);
    transition: box-shadow .18s ease, border-color .18s ease, transform .12s ease, background .18s ease;
  }
  .input-dark:hover{
    box-shadow: inset 0 1px 0 rgba(255,255,255,.09);
    border-color: rgba(74,222,128,.5);
  }
  .input-dark:focus{
    border-color: rgba(74,222,128,.75);
    box-shadow: 0 0 0 1px rgba(45,212,191,.4), 0 0 0 7px rgba(45,212,191,.12), inset 0 1px 0 rgba(255,255,255,.09);
    transform: translateY(-1px);
    background: radial-gradient(circle at 0 0,rgba(34,197,94,.22),transparent 55%),
                radial-gradient(circle at 100% 100%,rgba(56,189,248,.22),transparent 55%),
                rgba(15,23,42,.98);
  }

  .liquid-btn{
    --bg1:#19d3a6;--bg2:#5df0ff;--bg3:#a3ff7a;--glow:0 0 24px rgba(99,255,178,.35);
    padding:.9rem 1.15rem;
    border-radius:9999px;
    border:1px solid rgba(255,255,255,.12);
    background:
      radial-gradient(120% 120% at 10% 10%,var(--bg1),transparent 60%),
      radial-gradient(120% 120% at 90% 90%,var(--bg2),transparent 60%),
      radial-gradient(120% 120% at 50% 50%,var(--bg3),transparent 60%),
      linear-gradient(135deg,rgba(255,255,255,.06),rgba(255,255,255,.02));
    color:#0b1010;
    font-weight:700;
    letter-spacing:.2px;
    filter:drop-shadow(var(--glow));
    transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease,filter .2s ease;
    isolation:isolate;
    -webkit-filter:url(#gooey);
    filter:url(#gooey);
    overflow:visible;
    min-width: 150px;
    text-align:center;
  }
  .liquid-btn:hover{
    transform:translateY(-1px) scale(1.02);
    box-shadow:0 16px 40px rgba(86,255,206,.25),inset 0 0 0 9999px rgba(255,255,255,.02);
    border-color:rgba(255,255,255,.2);
  }
  .liquid-btn:active{
    transform:translateY(0) scale(.98);
    box-shadow:0 6px 26px rgba(16,185,129,.42);
  }
  .liquid-btn .label{
    position:relative;
    z-index:2;
    color:#071312;
    font-size:.8rem;
    text-transform:uppercase;
    letter-spacing:.17em;
  }
  .liquid-btn .blob{
    position:absolute;
    border-radius:9999px;
    background:
      radial-gradient(circle at 30% 30%,rgba(255,255,255,.35),transparent 40%),
      currentColor;
    opacity:.9;
    z-index:1;
    mix-blend-mode:screen;
    animation:float 8s ease-in-out infinite;
  }
  .liquid-btn .blob-1{width:28px;height:28px;left:6px;top:-8px;color:#19d3a6;animation-delay:-.2s}
  .liquid-btn .blob-2{width:22px;height:22px;left:36px;top:40%;color:#5df0ff;animation-delay:-1.1s}
  .liquid-btn .blob-3{width:30px;height:30px;right:8px;bottom:-10px;color:#a3ff7a;animation-delay:-.6s}
  .liquid-btn .blob-4{width:18px;height:18px;right:38px;top:-6px;color:#34f3d4;animation-delay:-1.6s}
  .liquid-btn:hover .blob-1{transform:translateY(2px) scale(1.1)}
  .liquid-btn:hover .blob-3{transform:translateY(-2px) scale(1.08)}

  @keyframes float{
    0%,100%{transform:translate(0,0) scale(1)}
    25%{transform:translate(2px,-2px) scale(1.03)}
    50%{transform:translate(0,2px) scale(.98)}
    75%{transform:translate(-2px,0) scale(1.02)}
  }

  .category-chip.is-active{
    border-color: rgba(74,222,128,.8);
    background: rgba(16,185,129,.18);
    color: #bbf7d0;
    box-shadow: 0 0 0 1px rgba(16,185,129,.6);
  }

  .animate-slow-pan{
    animation:slow-pan 26s linear infinite alternate;
  }
  @keyframes slow-pan{
    0%{transform:translate3d(0,0,0) scale(1)}
    100%{transform:translate3d(-12px,-16px,0) scale(1.03)}
  }
</style>
@endsection

@section('scripts')
<script>
  // Alpine controller for modal
  function materialModal({ visibleInitially = false } = {}){
    return {
      visible: visibleInitially,
      open(){
        this.visible = true;
        this.$nextTick(() => {
          const el = document.querySelector('input[name="material_name"]');
          el && el.focus();
        });
      },
      close(){ this.visible = false; }
    }
  }

  // Category chips -> select + active visual state
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-chip-category]');
    if(!btn) return;
    const sel = document.querySelector('select[name="category"]');
    if (!sel) return;
    const value = btn.dataset.chipCategory;
    sel.value = value;
    sel.dispatchEvent(new Event('change'));

    // toggle active class
    document.querySelectorAll('.category-chip').forEach(chip => chip.classList.remove('is-active'));
    btn.classList.add('is-active');
  });

  // SKU generator
  function slugify(str){
    return (str||'').toUpperCase()
      .replace(/[^A-Z0-9]+/g,'-')
      .replace(/(^-|-$)/g,'')
      .replace(/-{2,}/g,'-');
  }

  document.getElementById('skuGen')?.addEventListener('click', () => {
    const nameInp = document.querySelector('input[name="material_name"]');
    const selCat  = document.querySelector('select[name="category"]');
    const skuInp  = document.getElementById('skuInput');
    const base = slugify(nameInp?.value || '');
    if(!base || !skuInp) return;
    let prefix = 'RM';
    const map = {
      'Primary Raw Materials':'PRM','Meat Cuts & Trimmings':'CUT','Fats / Skins':'FAT','Salt':'SALT',
      'Curing Agents (Nitrite/Nitrate)':'CUR','Phosphates':'PHO','Spices & Seasonings':'SPC',
      'Fillers & Binders':'FLR','Sugars':'SUG','Water / Ice':'H2O','Smoke Materials':'SMK',
      'Casings':'CAS','Packaging Films & Bags':'PKG','Labels & Cartons':'LBL','Cleaning & Sanitation (Non-food)':'CLN'
    };
    if(selCat?.value) prefix = map[selCat.value] || 'RM';
    skuInp.value = `${prefix}-${base}`.slice(0,64);
  });

  // Submit micro-interaction: disable button + "Saving..."
  document.getElementById('materialForm')?.addEventListener('submit', function(e){
    const btn = document.getElementById('btnSave');
    const label = btn?.querySelector('.label');
    if(btn && label){
      btn.disabled = true;
      btn.classList.add('opacity-80','cursor-wait');
      label.textContent = 'Saving...';
    }
  });
</script>
@endsection
