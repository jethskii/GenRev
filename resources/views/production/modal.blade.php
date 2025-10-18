{{-- Global Add/Quick Add Production Modal (supports creating NEW product inline) --}}
<div id="addModal" class="fixed inset-0 z-[9999] hidden">
  {{-- Backdrop --}}
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeAddModal()"></div>

  {{-- Panel --}}
  <div class="relative mx-auto my-10 w-[92%] max-w-xl glass section-liquid-shine rounded-2xl shadow-xl p-6 border border-dark-line text-white animate-fadeIn">
    <button type="button" onclick="closeAddModal()" class="absolute top-2 right-4 text-2xl font-bold hover:text-red-400">&times;</button>
    <h3 class="text-xl font-semibold mb-4">Add Production</h3>

    <form id="go_form" action="{{ route('production.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
      @csrf

      {{-- Product selector / New product toggle --}}
      <div class="flex items-center justify-between">
        <label class="block text-sm mb-1">Product</label>
        <button type="button" id="go_toggle_new"
                class="text-xs underline text-emerald-300 hover:text-emerald-200">+ Add new product</button>
      </div>

      {{-- Existing product select --}}
      <select id="go_product_id" name="product_id"
              class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
              required>
        <option value="" selected disabled>— Select product —</option>
        @foreach ($products as $p)
          <option value="{{ $p->id }}"
                  data-name="{{ $p->product_name }}"
                  data-shelf="{{ (int)($p->shelf_life_days ?? 7) }}"
                  data-price="{{ (float)($p->default_price ?? 0) }}"
                  data-img="{{ $p->image_url ?? '' }}">
            {{ $p->product_name }}
          </option>
        @endforeach
      </select>

      {{-- NEW product fields (hidden by default) --}}
      <div id="go_new_wrap" class="hidden space-y-3">
        <div>
          <label class="block text-sm mb-1">New Product Name</label>
          <input id="go_new_name" name="product_name" type="text"
                 class="w-full rounded-xl bg-white/10 border border-white/10 px-3 py-2">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm mb-1">Category (optional)</label>
            <input id="go_new_category" name="category" type="text"
                   placeholder="Beef, Pork, Chicken…"
                   class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2">
          </div>
          <div>
            <label class="block text-sm mb-1">Shelf Life (days)</label>
            <input id="go_new_shelf" name="shelf_life_days" type="number" min="1" max="365"
                   class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" value="7">
          </div>
        </div>
      </div>

      {{-- Image preview + upload --}}
      <div>
        <label class="block text-sm mb-1">Product Image</label>
        <div class="flex items-center gap-3">
          <div class="h-20 w-28 rounded-xl border border-white/10 bg-white/5 overflow-hidden">
            <img id="go_preview" src="{{ asset('images/default-product.png') }}" alt="Preview" class="h-full w-full object-cover">
          </div>
          <input id="go_image" name="image" type="file" accept="image/*"
                 class="flex-1 block w-full text-sm text-white file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-2 file:text-white hover:file:bg-emerald-700 cursor-pointer"/>
        </div>
        <p class="text-xs text-white/60 mt-1">PNG/JPG up to 5MB. If you select an existing product, this can update its image.</p>
      </div>

      {{-- Batch --}}
      <div>
        <label class="block text-sm mb-1">Batch Number</label>
        <input id="go_batch" name="batch_number"
               class="w-full rounded-xl bg-white/10 border border-white/10 px-3 py-2 text-white/90 cursor-not-allowed"
               readonly required>
      </div>

      {{-- Forecast + Produced --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Forecasted Demand (kg)</label>
          <input id="go_fc" name="forecasted_demand" type="number" step="0.01" min="0"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2">
        </div>
        <div>
          <label class="block text-sm mb-1">Produced Quantity (kg)</label>
          {{-- Controller expects INT for current_inventory/quantity --}}
          <input id="go_prod_qty" name="current_inventory" type="number" step="1" min="1"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" required>
        </div>
      </div>

      {{-- Cost + Prices --}}
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="sm:col-span-1">
          <label class="block text-sm mb-1">Unit Cost (₱)</label>
          <input id="go_cost" name="unit_cost" type="number" step="0.01" min="0"
                 class="w-full rounded-xl bg-white/10 border border-white/10 px-3 py-2 text-white/90" placeholder="auto">
        </div>
        <div>
          <label class="block text-sm mb-1">Price per Pack (₱)</label>
          <input id="go_price_pack" name="unit_price_pack" type="number" step="0.01" min="0"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2">
        </div>
        <div>
          <label class="block text-sm mb-1">Price per Bag (₱)</label>
          <input id="go_price_bag" name="unit_price_bag" type="number" step="0.01" min="0"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2">
        </div>
      </div>

      {{-- Dates --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Production Date</label>
          <input id="go_prod_date" name="production_date" type="date"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm mb-1">Expiration Date</label>
          <input id="go_exp_date" name="expiration_date" type="date"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2">
          <p class="text-xs text-[var(--muted,#A3B4A7)] mt-1">Auto from shelf life. You can override.</p>
        </div>
      </div>

      {{-- Optional Sale on create (kept; backend ignores unknown fields) --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Order Date</label>
          <input id="go_order_date" name="order_date" type="date"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2">
        </div>
        <div>
          <label class="block text-sm mb-1">Order Quantity (kg)</label>
          <input id="go_order_qty" name="order_quantity_kg" type="number" step="0.01" min="0"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Customer (optional)</label>
          <input name="customer_name"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2"
                 placeholder="Walk-in, Distributor A, etc.">
        </div>
        <div>
          <label class="block text-sm mb-1">Notes</label>
          <input name="notes" class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" placeholder="Special handling, delivery, etc.">
        </div>
      </div>

      <button class="w-full mt-2 px-4 py-2 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] font-semibold shadow hover:opacity-90 transition">
        Save
      </button>
    </form>
  </div>
</div>

<style>
@keyframes fadeIn { from{opacity:0;transform:scale(.98)} to{opacity:1;transform:scale(1)} }
.animate-fadeIn{ animation:fadeIn .2s ease-out }
</style>

<script>
const $ = (sel) => document.querySelector(sel);

function openAddModal(){
  resetGO();
  genBatch();
  setTodayDefaults();
  $('#addModal')?.classList.remove('hidden');
  $('#addModal')?.classList.add('flex');
}
function closeAddModal(){
  $('#addModal')?.classList.add('hidden');
  $('#addModal')?.classList.remove('flex');
}
function genBatch(){
  const now = new Date(), p = n => String(n).padStart(2,'0');
  $('#go_batch').value = `BATCH-${now.getFullYear()}${p(now.getMonth()+1)}${p(now.getDate())}-${p(now.getHours())}${p(now.getMinutes())}${p(now.getSeconds())}`;
}
function setTodayDefaults(){
  const d = new Date(), y=d.getFullYear(), m=String(d.getMonth()+1).padStart(2,'0'), day=String(d.getDate()).padStart(2,'0');
  $('#go_prod_date').value = `${y}-${m}-${day}`;
  $('#go_order_date').value = `${y}-${m}-${day}`;
}
function resetGO(){
  [
    'go_fc','go_prod_qty','go_cost',
    'go_price_pack','go_price_bag',
    'go_prod_date','go_exp_date','go_order_date','go_order_qty',
    'go_new_name','go_new_category','go_new_shelf'
  ].forEach(id => { const el = $('#'+id); if(el){ el.value = ''; } });
  $('#go_new_shelf')?.setAttribute('value','7');
  if ($('#go_product_id')) $('#go_product_id').selectedIndex = 0;
  $('#go_preview').src = `{{ asset('images/default-product.png') }}`;
  setModeExisting(); // default
}

function setModeNew(){
  $('#go_new_wrap').classList.remove('hidden');
  $('#go_product_id').setAttribute('disabled', 'disabled');
  $('#go_product_id').removeAttribute('required');
  $('#go_toggle_new').textContent = '← Use existing product';
  // Require new product fields
  $('#go_new_name').required = true;
}
function setModeExisting(){
  $('#go_new_wrap').classList.add('hidden');
  $('#go_product_id').removeAttribute('disabled');
  $('#go_product_id').setAttribute('required','required');
  $('#go_toggle_new').textContent = '+ Add new product';
  $('#go_new_name').required = false;
}

// Toggle
$('#go_toggle_new')?.addEventListener('click', () => {
  if ($('#go_new_wrap').classList.contains('hidden')) setModeNew(); else setModeExisting();
});

// Existing product change → autofill + preview + auto-expiry
$('#go_product_id')?.addEventListener('change', async (e) => {
  const opt = e.target.selectedOptions[0]; if (!opt) return;

  // preview image (if any)
  const img = opt.dataset.img;
  $('#go_preview').src = img && img.length ? img : `{{ asset('images/default-product.png') }}`;

  // optional default price used as a convenient starting point
  const price = Number(opt.dataset.price || 0);
  if (!isNaN(price)) {
    // If you have separate defaults, you can split them; for now set both
    $('#go_price_pack').value = price.toFixed(2);
    $('#go_price_bag').value  = price.toFixed(2);
  }

  // fetch more info by name (unit cost, forecast) from controller
  try{
    const res = await fetch(`{{ url('/production/info') }}/${encodeURIComponent(opt.dataset.name)}`);
    if (res.ok){
      const j = await res.json();
      if (j.unit_cost != null)  $('#go_cost').value = Number(j.unit_cost).toFixed(2);
      if (j.forecasted_demand != null) $('#go_fc').value = Number(j.forecasted_demand);
      const shelf = Number(opt.dataset.shelf || j.shelf_life_days || 7);
      autoExpiryFrom($('#go_prod_date').value, shelf);
    }
  }catch(_){}
});

// Image preview (new upload)
$('#go_image')?.addEventListener('change', (e) => {
  const f = e.target.files?.[0]; if (!f) return;
  const r = new FileReader();
  r.onload = () => { $('#go_preview').src = r.result; };
  r.readAsDataURL(f);
});

// Recalculate expiry when production date changes
$('#go_prod_date')?.addEventListener('change', () => {
  const opt = $('#go_product_id')?.selectedOptions?.[0];
  const shelf = opt ? Number(opt.dataset.shelf || 7) : Number($('#go_new_shelf')?.value || 7);
  autoExpiryFrom($('#go_prod_date').value, shelf);
});

function autoExpiryFrom(prodISO, shelfDays){
  if (!prodISO) return;
  const d = new Date(prodISO); d.setDate(d.getDate() + Number(shelfDays||7));
  $('#go_exp_date').value = d.toISOString().slice(0,10);
  $('#go_exp_date').min   = prodISO;
}
</script>
