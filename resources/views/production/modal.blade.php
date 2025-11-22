{{-- Global Add/Quick Add Production Modal (supports creating NEW product inline) --}}
<div id="addModal" class="fixed inset-0 z-[9999] hidden items-center justify-center global-add-backdrop">
  {{-- Backdrop --}}
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeAddModal()"></div>

  {{-- Panel --}}
  <div id="globalAddPanel"
       class="relative mx-auto my-10 w-[92%] max-w-xl rounded-2xl shadow-[0_24px_80px_rgba(15,23,42,0.65)]
              border border-white/10 bg-gradient-to-br from-slate-900/90 via-slate-900/80 to-slate-950/95
              overflow-hidden global-add-panel">

    {{-- Glow strip --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-emerald-400 via-sky-400 to-fuchsia-500 opacity-80"></div>

    {{-- Corner orbs --}}
    <div class="pointer-events-none absolute -top-16 -left-10 h-40 w-40 rounded-full bg-emerald-500/15 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-16 -right-10 h-40 w-40 rounded-full bg-sky-500/15 blur-3xl"></div>

    <button type="button"
            onclick="closeAddModal()"
            class="absolute top-2 right-3 text-2xl font-bold text-slate-300 hover:text-red-400 transition-colors">&times;</button>

    <div class="relative px-6 pt-5 pb-6 text-slate-50">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
          <span class="inline-flex h-8 w-8 items-center justify-center rounded-2xl
                       bg-gradient-to-br from-emerald-400 via-sky-400 to-indigo-500
                       text-slate-950 font-bold text-lg shadow-lg shadow-emerald-500/40 animate-pulse-slow">
            +
          </span>
          <div>
            <h3 class="text-xl font-semibold leading-tight">Add Production</h3>
            <p class="text-xs text-slate-400">Create a new batch and optionally update product metadata.</p>
          </div>
        </div>
      </div>

      <form id="go_form"
            action="{{ route('production.store') }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-4">
        @csrf

        {{-- Product selector / New product toggle --}}
        <div class="flex items-center justify-between gap-2">
          <label class="block text-sm mb-1">Product</label>
          <button type="button" id="go_toggle_new"
                  class="text-[11px] px-2 py-1 rounded-full border border-emerald-400/40
                         bg-emerald-500/10 text-emerald-200 hover:bg-emerald-500/20 transition">
            + Add new product
          </button>
        </div>

        {{-- Existing product select --}}
        <select id="go_product_id" name="product_id"
                class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2
                       text-sm text-slate-50 placeholder-slate-400
                       focus:outline-none focus:ring-2 focus:ring-emerald-400/80 focus:border-emerald-300/80"
                required>
          <option value="" selected disabled>— Select product —</option>
          @foreach ($products as $p)
            <option value="{{ $p->id }}"
                    data-name="{{ $p->product_name }}"
                    data-shelf="{{ (int)($p->shelf_life_days ?? 7) }}"
                    data-price="{{ (float)($p->default_price ?? 0) }}"
                    data-img="{{ $p->card_image_url ?? $p->image_url ?? '' }}">
              {{ $p->product_name }}
            </option>
          @endforeach
        </select>

        {{-- NEW product fields (hidden by default) --}}
        <div id="go_new_wrap" class="hidden space-y-3 border border-white/10 rounded-xl bg-white/5 px-3 py-3">
          <div>
            <label class="block text-sm mb-1">New Product Name</label>
            <input id="go_new_name" name="product_name" type="text"
                   class="w-full rounded-xl bg-slate-900/60 border border-white/15 px-3 py-2 text-sm text-slate-50 placeholder-slate-500"
                   placeholder="e.g., Premium Skinless, Garlic Skinless">
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm mb-1">Category (optional)</label>
              <input id="go_new_category" name="category" type="text"
                     placeholder="Beef, Pork, Chicken…"
                     class="w-full rounded-xl bg-slate-900/60 border border-white/15 px-3 py-2 text-sm text-slate-50 placeholder-slate-500">
            </div>
            <div>
              <label class="block text-sm mb-1">Shelf Life (days)</label>
              <input id="go_new_shelf" name="shelf_life_days" type="number" min="1" max="365"
                     class="w-full rounded-xl bg-slate-900/60 border border-white/15 px-3 py-2 text-sm text-slate-50"
                     value="7">
            </div>
          </div>
          <p class="text-[11px] text-slate-400">Shelf life is used to auto-calculate expiration dates.</p>
        </div>

        {{-- Image preview + upload --}}
        <div>
          <label class="block text-sm mb-1">Product Image</label>
          <div class="flex items-center gap-3">
            <div class="h-20 w-28 rounded-xl border border-white/10 bg-white/5 overflow-hidden
                        shadow-inner shadow-slate-900/80">
              <img id="go_preview"
                   src="{{ asset('images/default-product.png') }}"
                   alt="Preview"
                   class="h-full w-full object-cover transition-transform duration-200 hover:scale-[1.03]">
            </div>
            <div class="flex-1 flex flex-col gap-1">
              <input id="go_image" name="image" type="file" accept="image/*"
                     class="block w-full text-xs text-slate-100
                            file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-500/90
                            file:px-3 file:py-1.5 file:text-xs file:font-medium
                            hover:file:bg-emerald-400 cursor-pointer" />
              <button type="button" id="go_image_clear"
                      class="self-start text-[11px] text-slate-400 hover:text-slate-200 mt-0.5">
                Clear image
              </button>
            </div>
          </div>
          <p class="text-[11px] text-slate-400 mt-1">
            JPG, PNG, or WebP, max 4MB. Updating the image will also update the product card.
          </p>
        </div>

        {{-- Batch display (label only, controller still ensures sequential numeric) --}}
        <div>
          <label class="block text-sm mb-1">Batch Number (preview)</label>
          <input id="go_batch" name="batch_number"
                 class="w-full rounded-xl bg-slate-900/70 border border-white/10 px-3 py-2 text-sm
                        text-amber-200 font-semibold tracking-wide cursor-not-allowed"
                 readonly>
          <p class="text-[11px] text-slate-400 mt-1">
            Actual batch number is stored as a sequential integer per product. This label is for your reference.
          </p>
        </div>

        {{-- Forecast + Produced --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm mb-1">Forecasted Demand (kg)</label>
            <input id="go_fc" name="forecasted_demand" type="number" step="0.01" min="0"
                   class="w-full rounded-xl bg-slate-900/60 border border-white/10 px-3 py-2 text-sm text-slate-50">
          </div>
          <div>
            <label class="block text-sm mb-1">Produced Quantity (kg)</label>
            {{-- Controller infers quantity/current_inventory via inferQuantity() --}}
            <input id="go_prod_qty" name="current_inventory" type="number" step="1" min="1"
                   class="w-full rounded-xl bg-slate-900/60 border border-white/10 px-3 py-2 text-sm text-slate-50"
                   required>
          </div>
        </div>

        {{-- Cost + Prices --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label class="block text-sm mb-1">Unit Cost (₱)</label>
            <input id="go_cost" name="unit_cost" type="number" step="0.01" min="0"
                   class="w-full rounded-xl bg-slate-900/60 border border-white/15 px-3 py-2 text-sm text-slate-50"
                   placeholder="auto">
          </div>
          <div>
            <label class="block text-sm mb-1">Price per Pack (₱)</label>
            <input id="go_price_pack" name="unit_price_pack" type="number" step="0.01" min="0"
                   class="w-full rounded-xl bg-slate-900/60 border border-white/15 px-3 py-2 text-sm text-slate-50">
          </div>
          <div>
            <label class="block text-sm mb-1">Price per Bag (₱)</label>
            <input id="go_price_bag" name="unit_price_bag" type="number" step="0.01" min="0"
                   class="w-full rounded-xl bg-slate-900/60 border border-white/15 px-3 py-2 text-sm text-slate-50">
          </div>
        </div>

        {{-- Dates --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm mb-1">Production Date</label>
            <input id="go_prod_date" name="production_date" type="date"
                   class="w-full rounded-xl bg-slate-900/60 border border-white/10 px-3 py-2 text-sm text-slate-50"
                   required>
          </div>
          <div>
            <label class="block text-sm mb-1">Expiration Date</label>
            <input id="go_exp_date" name="expiration_date" type="date"
                   class="w-full rounded-xl bg-slate-900/60 border border-white/10 px-3 py-2 text-sm text-slate-50">
            <p class="text-[11px] text-slate-400 mt-1">
              Auto-calculated from shelf life. You can override if needed.
            </p>
          </div>
        </div>

        {{-- Optional Sale on create (safe; backend ignores unknown fields) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm mb-1">Order Date</label>
            <input id="go_order_date" name="order_date" type="date"
                   class="w-full rounded-xl bg-slate-900/60 border border-white/10 px-3 py-2 text-sm text-slate-50">
          </div>
          <div>
            <label class="block text-sm mb-1">Order Quantity (kg)</label>
            <input id="go_order_qty" name="order_quantity_kg" type="number" step="0.01" min="0"
                   class="w-full rounded-xl bg-slate-900/60 border border-white/10 px-3 py-2 text-sm text-slate-50">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm mb-1">Customer (optional)</label>
            <input name="customer_name"
                   class="w-full rounded-xl bg-slate-900/60 border border-white/10 px-3 py-2 text-sm text-slate-50"
                   placeholder="Walk-in, Distributor A, etc.">
          </div>
          <div>
            <label class="block text-sm mb-1">Notes</label>
            <input name="notes"
                   class="w-full rounded-xl bg-slate-900/60 border border-white/10 px-3 py-2 text-sm text-slate-50"
                   placeholder="Special handling, delivery, etc.">
          </div>
        </div>

        <button
          class="w-full mt-2 px-4 py-2.5 rounded-xl
                 bg-gradient-to-r from-amber-300 via-amber-400 to-amber-500
                 text-slate-950 text-sm font-semibold tracking-wide shadow-lg
                 hover:brightness-105 active:scale-[0.98] transition">
          Save Production
        </button>
      </form>
    </div>
  </div>
</div>

<style>
.global-add-backdrop{
  animation:backdropIn .18s ease-out both;
}
.global-add-panel{
  transform-origin:center;
}
.global-add-panel.global-add-in{
  animation:panelPopIn .22s ease-out forwards;
}
.global-add-panel.global-add-out{
  animation:panelPopOut .18s ease-in forwards;
}
@keyframes backdropIn{
  from{opacity:0;}
  to{opacity:1;}
}
@keyframes panelPopIn{
  0%{opacity:0;transform:translateY(18px) scale(.94);}
  100%{opacity:1;transform:translateY(0) scale(1);}
}
@keyframes panelPopOut{
  0%{opacity:1;transform:translateY(0) scale(1);}
  100%{opacity:0;transform:translateY(12px) scale(.9);}
}
.animate-pulse-slow{
  animation:pulseSlow 1.8s ease-in-out infinite;
}
@keyframes pulseSlow{
  0%,100%{transform:scale(1);opacity:1;}
  50%{transform:scale(1.05);opacity:.9;}
}
</style>

<script>
const $g = (sel) => document.querySelector(sel);

function openAddModal(){
  resetGO();
  setTodayDefaults();
  const modal = $g('#addModal');
  const panel = $g('#globalAddPanel');
  if (!modal || !panel) return;
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  panel.classList.remove('global-add-out');
  void panel.offsetWidth; // restart animation
  panel.classList.add('global-add-in');
}
function closeAddModal(){
  const modal = $g('#addModal');
  const panel = $g('#globalAddPanel');
  if (!modal || !panel) return;
  panel.classList.remove('global-add-in');
  panel.classList.add('global-add-out');
  setTimeout(() => {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }, 170);
}

function setTodayDefaults(){
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth()+1).padStart(2,'0');
  const day = String(d.getDate()).padStart(2,'0');
  const iso = `${y}-${m}-${day}`;
  const prod = $g('#go_prod_date');
  const order = $g('#go_order_date');
  if (prod) prod.value = iso;
  if (order) order.value = iso;
}

function resetGO(){
  [
    'go_fc','go_prod_qty','go_cost',
    'go_price_pack','go_price_bag',
    'go_prod_date','go_exp_date','go_order_date','go_order_qty',
    'go_new_name','go_new_category','go_new_shelf'
  ].forEach(id => {
    const el = $g('#'+id);
    if (!el) return;
    if (id === 'go_new_shelf') el.value = '7';
    else el.value = '';
  });
  const sel = $g('#go_product_id');
  if (sel) sel.selectedIndex = 0;
  const preview = $g('#go_preview');
  if (preview) preview.src = `{{ asset('images/default-product.png') }}`;
  const batch = $g('#go_batch');
  if (batch) batch.value = 'Select a product…';
  setModeExisting(); // default
}

function setModeNew(){
  const wrap = $g('#go_new_wrap');
  const sel  = $g('#go_product_id');
  const btn  = $g('#go_toggle_new');
  if (wrap) wrap.classList.remove('hidden');
  if (sel){
    sel.setAttribute('disabled','disabled');
    sel.removeAttribute('required');
  }
  if (btn) btn.textContent = '← Use existing product';
  const name = $g('#go_new_name');
  if (name) { name.required = true; name.focus(); }
  const batch = $g('#go_batch');
  if (batch) batch.value = 'BATCH #1';
}
function setModeExisting(){
  const wrap = $g('#go_new_wrap');
  const sel  = $g('#go_product_id');
  const btn  = $g('#go_toggle_new');
  if (wrap) wrap.classList.add('hidden');
  if (sel){
    sel.removeAttribute('disabled');
    sel.setAttribute('required','required');
  }
  if (btn) btn.textContent = '+ Add new product';
  const name = $g('#go_new_name');
  if (name) name.required = false;
  const batch = $g('#go_batch');
  if (batch) batch.value = 'Select a product…';
}

// Toggle new/existing
$g('#go_toggle_new')?.addEventListener('click', () => {
  const wrap = $g('#go_new_wrap');
  if (!wrap) return;
  if (wrap.classList.contains('hidden')) setModeNew();
  else setModeExisting();
});

// Quick-add payload: get next batch label + suggested dates for existing product
async function prefillFromQuickAdd(productId){
  if (!productId) return;
  try {
    const res = await fetch(`/production/quick-add/${productId}`, {
      headers:{'X-Requested-With':'XMLHttpRequest'}
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const d = await res.json();
    const batch = $g('#go_batch');
    if (batch){
      // pretty label from controller: "BATCH #5"
      batch.value = d.batch_label || `BATCH #${d.next_batch_number || 1}`;
    }
    const prod = $g('#go_prod_date');
    const exp  = $g('#go_exp_date');
    if (prod && d.production_date) prod.value = d.production_date;
    if (exp  && d.expiration_date) exp.value  = d.expiration_date;
  } catch(e){
    console.warn('quick-add payload failed', e);
    const batch = $g('#go_batch');
    if (batch) batch.value = 'BATCH #…';
  }
}

// Existing product change → autofill + preview + auto-expiry + batch
$g('#go_product_id')?.addEventListener('change', async (e) => {
  const opt = e.target.selectedOptions[0];
  if (!opt) return;

  const img = opt.dataset.img;
  const preview = $g('#go_preview');
  if (preview) preview.src = img && img.length ? img : `{{ asset('images/default-product.png') }}`;

  // optional default price; you can adjust later to separate pack/bag defaults
  const price = Number(opt.dataset.price || 0);
  if (!isNaN(price) && price > 0) {
    const pack = $g('#go_price_pack');
    const bag  = $g('#go_price_bag');
    if (pack && !pack.value) pack.value = price.toFixed(2);
    if (bag  && !bag.value)  bag.value  = price.toFixed(2);
  }

  // fetch more info by name (unit cost, forecast) from controller
  try{
    const res = await fetch(`{{ url('/production/info') }}/${encodeURIComponent(opt.dataset.name)}`);
    if (res.ok){
      const j = await res.json();
      const cost = $g('#go_cost');
      const fc   = $g('#go_fc');
      if (cost && !cost.value && j.unit_cost != null)  cost.value = Number(j.unit_cost).toFixed(2);
      if (fc   && !fc.value && j.forecasted_demand != null) fc.value = Number(j.forecasted_demand);
      const shelf = Number(opt.dataset.shelf || j.shelf_life_days || 7);
      autoExpiryFrom($g('#go_prod_date')?.value, shelf);
    }
  }catch(_){}

  await prefillFromQuickAdd(Number(opt.value || 0));
});

// Image preview (new upload)
$g('#go_image')?.addEventListener('change', (e) => {
  const f = e.target.files?.[0]; if (!f) return;
  const r = new FileReader();
  r.onload = () => {
    const img = $g('#go_preview');
    if (img) img.src = r.result;
  };
  r.readAsDataURL(f);
});
$g('#go_image_clear')?.addEventListener('click', () => {
  const input = $g('#go_image');
  const img   = $g('#go_preview');
  if (input) input.value = '';
  if (img) img.src = `{{ asset('images/default-product.png') }}`;
});

// Recalculate expiry when production date or shelf changes
$g('#go_prod_date')?.addEventListener('change', () => {
  const opt   = $g('#go_product_id')?.selectedOptions?.[0];
  const shelf = opt ? Number(opt.dataset.shelf || 7)
                    : Number($g('#go_new_shelf')?.value || 7);
  autoExpiryFrom($g('#go_prod_date')?.value, shelf);
});
$g('#go_new_shelf')?.addEventListener('change', () => {
  const prod = $g('#go_prod_date')?.value;
  if (!prod) return;
  const shelf = Number($g('#go_new_shelf')?.value || 7);
  autoExpiryFrom(prod, shelf);
});

function autoExpiryFrom(prodISO, shelfDays){
  if (!prodISO) return;
  const d = new Date(prodISO);
  d.setDate(d.getDate() + Number(shelfDays||7));
  const iso = d.toISOString().slice(0,10);
  const exp = $g('#go_exp_date');
  if (exp){
    exp.value = iso;
    exp.min   = prodISO;
  }
}
</script>
