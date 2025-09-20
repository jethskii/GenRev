{{-- Add Material (Liquid / Gooey UI) - Animated Modal --}}
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
@endphp

{{-- Invisible SVG filter for gooey effect --}}
<svg class="absolute w-0 h-0"><defs>
  <filter id="gooey"><feGaussianBlur in="SourceGraphic" stdDeviation="7" result="blur"/><feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 22 -10" result="goo"/><feBlend in="SourceGraphic" in2="goo"/></filter>
</defs></svg>

<div x-data="materialModal()" x-id="['modal-title']" class="bg-dark-bg text-white border border-dark-line p-6 rounded-2xl shadow-md">
  <div class="flex items-center justify-between mb-6">
    <div>
      <h2 class="text-2xl font-bold tracking-tight">Raw Materials</h2>
      <p class="text-sm text-gray-400">Ingredients, additives, and packaging inputs used in meat production.</p>
    </div>

    {{-- Trigger to open modal --}}
    <button @click="open()" type="button" class="px-3 py-2 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-sm">
      ➕ Add Material
    </button>
  </div>

  {{-- You can keep your table/list here… --}}
  <div class="text-white/50 text-sm">No materials found.</div>

  {{-- Animated Modal --}}
  <template x-teleport="body">
    <div
      x-show="visible"
      x-transition.opacity.duration.200ms
      class="fixed inset-0 z-[60] bg-black/60 backdrop-blur-sm"
      @keydown.window.escape="close()"
      aria-modal="true" role="dialog" :aria-labelledby="$id('modal-title')"
    >
      {{-- Click outside to close --}}
      <div class="absolute inset-0" @click.self="close()"></div>

      <div
        x-show="visible"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0 translate-y-3"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-3"
        class="mx-auto mt-14 w-[95%] max-w-3xl rounded-2xl border border-white/10
               bg-gradient-to-br from-[#0f1c0f]/95 via-[#162616]/95 to-[#1f3a1f]/95
               shadow-2xl"
        @click.stop
      >
        <div class="p-6 md:p-8">
          <div class="flex items-start justify-between mb-5">
            <div>
              <h3 :id="$id('modal-title')" class="text-xl md:text-2xl font-semibold text-green-300 drop-shadow">
                ➕ Add Material
              </h3>
              <p class="text-xs md:text-sm text-white/60">Create a new raw material with category, unit, pricing and stock thresholds.</p>
            </div>
            <button @click="close()" class="text-gray-400 hover:text-red-400 text-2xl leading-none">&times;</button>
          </div>

          {{-- Errors --}}
          @if($errors->any())
            <div class="mb-4 text-red-300 text-sm">
              <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
              </ul>
            </div>
          @endif

          <form method="POST" action="{{ route('materials.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @csrf

            <div class="md:col-span-2">
              <label class="label-dark">Name</label>
              <input name="material_name" value="{{ old('material_name') }}" required class="input-dark w-full"
                     placeholder="e.g., Pork Lean, Prague Powder #1, Vacuum Bag 300x400mm" />
            </div>

            <div>
              <label class="label-dark">Category</label>
              <select name="category" class="input-dark w-full">
                <option value="">— Select —</option>
                @foreach($categoryCatalog as $_c)
                  <option value="{{ $_c }}" @selected(old('category') === $_c)>{{ $_c }}</option>
                @endforeach
              </select>

              {{-- Quick-select chips --}}
              <div class="mt-2 flex flex-wrap gap-2">
                @foreach($categoryCatalog as $_c)
                  <button type="button" data-chip-category="{{ $_c }}"
                          class="px-2.5 py-1 rounded-full text-xs border border-white/10 bg-white/5 hover:bg-white/10">
                    {{ $_c }}
                  </button>
                @endforeach
              </div>
            </div>

            <div>
              <label class="label-dark">Unit of Measure</label>
              <select name="unit" class="input-dark w-full" required>
                @foreach($unitOptions as $v => $label)
                  <option value="{{ $v }}" @selected(old('unit') === $v)>{{ $label }}</option>
                @endforeach
              </select>
            </div>

            <div>
              <label class="label-dark">Default Unit Price (₱)</label>
              <input name="default_unit_price" type="number" min="0" step="0.01"
                     class="input-dark w-full" value="{{ old('default_unit_price', 0) }}" />
            </div>

            <div>
              <label class="label-dark">Quantity (kg)</label>
              <input name="quantity_kg" type="number" min="0" step="0.001"
                     class="input-dark w-full" value="{{ old('quantity_kg', 0) }}" />
            </div>

            <div>
              <label class="label-dark">Min Stock (kg) <span class="text-white/40 text-[10px]">(for low-stock alerts)</span></label>
              <input name="min_stock_kg" type="number" min="0" step="0.001"
                     class="input-dark w-full" value="{{ old('min_stock_kg') }}" />
            </div>

            <div class="md:col-span-2">
              <label class="label-dark">SKU (optional)</label>
              <div class="flex gap-2">
                <input id="skuInput" name="sku" class="input-dark w-full" placeholder="e.g., MT-PORK-LEAN" value="{{ old('sku') }}" />
                <button type="button" id="skuGen" class="px-3 py-2 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-sm">Auto</button>
              </div>
              <p class="text-[11px] text-white/50 mt-1">Tip: Use a readable pattern like <code>CAT-ITEM-VARIANT</code> (e.g., <code>PKG-VACBAG-300x400</code>).</p>
            </div>

            {{-- Action row --}}
            <div class="md:col-span-2 flex items-center justify-end gap-3 pt-2">
              <button type="button" @click="close()" class="px-4 py-2 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10">Cancel</button>

              <button type="submit" id="btnSave" class="liquid-btn relative group">
                <span class="label">Save Material</span>
                <span class="blob blob-1"></span>
                <span class="blob blob-2"></span>
                <span class="blob blob-3"></span>
                <span class="blob blob-4"></span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>
</div>

{{-- local styles for inputs + gooey button --}}
<style>
  .label-dark{ @apply text-xs text-gray-400 mb-1 block; }

  .input-dark{
    @apply px-3 py-2.5 rounded-xl w-full text-gray-100 outline-none border;
  }
  .input-dark{
    background: rgba(18,30,18,.75);
    border-color: rgba(74,222,128,.18); /* green-400/18 */
    box-shadow: inset 0 1px 0 rgba(255,255,255,.02);
    transition: box-shadow .2s ease, border-color .2s ease, transform .12s ease;
  }
  .input-dark:hover{ box-shadow: inset 0 1px 0 rgba(255,255,255,.06); }
  .input-dark:focus{
    border-color: rgba(74,222,128,.45);
    box-shadow: 0 0 0 4px rgba(74,222,128,.12), inset 0 1px 0 rgba(255,255,255,.06);
    transform: translateY(-1px);
  }

  .liquid-btn{
    --bg1:#19d3a6;--bg2:#5df0ff;--bg3:#a3ff7a;--glow:0 0 24px rgba(99,255,178,.35);
    padding:.9rem 1.15rem;border-radius:9999px;border:1px solid rgba(255,255,255,.12);
    background:
      radial-gradient(120% 120% at 10% 10%,var(--bg1),transparent 60%),
      radial-gradient(120% 120% at 90% 90%,var(--bg2),transparent 60%),
      radial-gradient(120% 120% at 50% 50%,var(--bg3),transparent 60%),
      linear-gradient(135deg,rgba(255,255,255,.06),rgba(255,255,255,.02));
    color:#0b1010;font-weight:700;letter-spacing:.2px;filter:drop-shadow(var(--glow));
    transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease;isolation:isolate;
    -webkit-filter:url(#gooey);filter:url(#gooey);overflow:visible
  }
  .liquid-btn:hover{transform:translateY(-1px) scale(1.015);box-shadow:0 10px 30px rgba(86,255,206,.15),inset 0 0 0 9999px rgba(255,255,255,.02);border-color:rgba(255,255,255,.18)}
  .liquid-btn:active{transform:translateY(0) scale(.99)}
  .liquid-btn .label{position:relative;z-index:2;color:#071312}
  .liquid-btn .blob{position:absolute;border-radius:9999px;background:radial-gradient(circle at 30% 30%,rgba(255,255,255,.35),transparent 40%),currentColor;opacity:.9;z-index:1;mix-blend-mode:screen;animation:float 8s ease-in-out infinite}
  .liquid-btn .blob-1{width:28px;height:28px;left:6px;top:-8px;color:#19d3a6;animation-delay:-.2s}
  .liquid-btn .blob-2{width:22px;height:22px;left:36px;top:40%;color:#5df0ff;animation-delay:-1.1s}
  .liquid-btn .blob-3{width:30px;height:30px;right:8px;bottom:-10px;color:#a3ff7a;animation-delay:-.6s}
  .liquid-btn .blob-4{width:18px;height:18px;right:38px;top:-6px;color:#34f3d4;animation-delay:-1.6s}
  .liquid-btn:hover .blob-1{transform:translateY(2px) scale(1.1)}
  .liquid-btn:hover .blob-3{transform:translateY(-2px) scale(1.08)}
  @keyframes float{0%,100%{transform:translate(0,0) scale(1)}25%{transform:translate(2px,-2px) scale(1.03)}50%{transform:translate(0,2px) scale(.98)}75%{transform:translate(-2px,0) scale(1.02)}}
</style>
@endsection

@section('scripts')
<script>
  // Alpine controller for modal
  function materialModal(){
    return {
      visible: false,
      open(){ this.visible = true; this.$nextTick(() => {
        // focus the first input for fast entry
        const el = document.querySelector('input[name="material_name"]');
        el && el.focus();
      });},
      close(){ this.visible = false; }
    }
  }

  // Category chips -> select
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-chip-category]');
    if(!btn) return;
    const sel = document.querySelector('select[name="category"]');
    sel.value = btn.dataset.chipCategory;
    sel.dispatchEvent(new Event('change'));
  });

  // SKU generator
  function slugify(str){
    return (str||'').toUpperCase().replace(/[^A-Z0-9]+/g,'-').replace(/(^-|-$)/g,'').replace(/-{2,}/g,'-');
  }
  document.getElementById('skuGen')?.addEventListener('click', () => {
    const nameInp = document.querySelector('input[name="material_name"]');
    const selCat  = document.querySelector('select[name="category"]');
    const skuInp  = document.getElementById('skuInput');
    const base = slugify(nameInp?.value || '');
    if(!base) return;
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
</script>
@endsection
