{{-- Global Add Order Modal (choose product) --}}
<div id="addModal" class="fixed inset-0 z-[9999] hidden">
  <!-- Backdrop -->
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeAddModal()"></div>

  <!-- Panel -->
  <div class="relative mx-auto my-10 w-[92%] max-w-md glass section-liquid-shine rounded-2xl shadow-xl p-6 border border-dark-line text-white animate-fadeIn">
    <button type="button" onclick="closeAddModal()" class="absolute top-2 right-4 text-2xl font-bold hover:text-red-400">&times;</button>
    <h3 class="text-xl font-semibold mb-4">Add Order</h3>

    <form id="globalOrderForm" method="POST" class="space-y-3">
      @csrf

      {{-- Product selector --}}
    <div>
    <label class="block text-sm mb-1">Product</label>
    <select id="go_product_id" class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400" required>
        <option value="" selected disabled>Select a product</option>
        @foreach ($products as $p)
        <option value="{{ $p->id }}" data-name="{{ $p->product_name }}"
                data-shelf="{{ (int)($p->shelf_life_days ?? 7) }}"
                data-price="{{ (float)($p->default_price ?? 0) }}"
                data-img="{{ $p->image_url ?? '' }}">
            {{ $p->product_name }}
        </option>
        @endforeach
    </select>
    </div>

    {{-- Image Preview + Upload --}}
<div class="mt-2">
            <label class="block text-sm mb-1">Product Image</label>

  <div class="flex items-center gap-3">
    {{-- Preview frame --}}
            <div class="h-20 w-20 flex-shrink-0 rounded-xl border border-white/10 bg-white/5 overflow-hidden">
            <img id="go_preview" src="/images/placeholder.png" alt="Preview"
            class="h-full w-full object-cover">
    </div>

    {{-- File input --}}
    <div class="flex-1">
             <input id="go_image" name="image" type="file" accept="image/*"
             class="block w-full text-sm text-white file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-2 file:text-white hover:file:bg-emerald-700 cursor-pointer"/>
             <p class="text-xs text-white/70 mt-1">PNG, JPG up to ~5MB. Upload to override the product’s default image for this order.</p>
         </div>
      </div>
    </div>


      {{-- Batch --}}
      <div>
        <label class="block text-sm mb-1">Batch Number</label>
        <input id="go_batch" name="batch_number"
               class="w-full rounded-xl bg-white/10 border border-white/10 px-3 py-2 text-white/90 cursor-not-allowed"
               readonly required>
      </div>

      {{-- Forecasted + Produced --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Forecasted Demand (kg)</label>
          <input id="go_fc" name="forecasted_demand" type="number" step="any"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400">
        </div>
        <div>
          <label class="block text-sm mb-1">Produced Quantity (kg)</label>
          <input id="go_prod_qty" name="produced_qty_kg" type="number" step="any"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400" required>
        </div>
      </div>

      {{-- Cost + Price --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Unit Cost (₱)</label>
          <input id="go_cost" name="unit_cost" type="number" step="any"
                 class="w-full rounded-xl bg-white/10 border border-white/10 px-3 py-2 text-white/90 cursor-not-allowed" readonly required>
        </div>
        <div>
          <label class="block text-sm mb-1">Unit Price (₱)</label>
          <input id="go_price" name="unit_price" type="number" step="any"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400" required>
        </div>
      </div>

      {{-- Production + Expiration --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Production Date</label>
          <input id="go_prod_date" name="production_date" type="date"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400" required>
        </div>
        <div>
          <label class="block text-sm mb-1">Expiration Date</label>
          <input id="go_exp_date" name="expiration_date" type="date"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400">
          <p class="text-xs text-[var(--muted,#A3B4A7)] mt-1">Auto from shelf life. You can override.</p>
        </div>
      </div>

      {{-- Sales (order) --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Order Date</label>
          <input id="go_order_date" name="order_date" type="date"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400" required>
        </div>
        <div>
          <label class="block text-sm mb-1">Order Quantity (kg)</label>
          <input id="go_order_qty" name="order_quantity_kg" type="number" step="any"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400" required>
        </div>
      </div>

      {{-- Optional --}}
      <div>
        <label class="block text-sm mb-1">Customer (optional)</label>
        <input name="customer_name"
               class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
               placeholder="Walk-in, Distributor A, etc.">
      </div>
      <div>
        <label class="block text-sm mb-1">Notes</label>
        <textarea name="notes" rows="2"
                  class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                  placeholder="Special handling, delivery date, etc."></textarea>
      </div>

      <button class="w-full mt-4 px-4 py-2 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] font-semibold shadow hover:opacity-90 transition">Add Order (Production + Sale)</button>
    </form>
  </div>
</div>

{{-- Tiny animation --}}
<style>
@keyframes fadeIn { from{opacity:0;transform:scale(.98)} to{opacity:1;transform:scale(1)} }
.animate-fadeIn{ animation:fadeIn .2s ease-out }
</style>

{{-- Global modal JS (unchanged behavior, UI-only updates) --}}
<script>
  const $ = id => document.getElementById(id);

  function openAddModal(){ resetGO(); genBatch('go_batch'); setTodayDefaults('go_prod_date','go_order_date'); $('addModal').classList.remove('hidden'); $('addModal').classList.add('flex'); }
  function closeAddModal(){ $('addModal').classList.add('hidden'); $('addModal').classList.remove('flex'); }

  function genBatch(id) {
    const now = new Date(), p = n => n.toString().padStart(2,'0');
    $(id).value = `BATCH-${now.getFullYear()}${p(now.getMonth()+1)}${p(now.getDate())}-${p(now.getHours())}${p(now.getMinutes())}${p(now.getSeconds())}`;
  }
  function setTodayDefaults(prodId, orderId) {
    const d = new Date(), y=d.getFullYear(), m=String(d.getMonth()+1).padStart(2,'0'), day=String(d.getDate()).padStart(2,'0');
    $(prodId).value = `${y}-${m}-${day}`;
    $(orderId).value = `${y}-${m}-${day}`;
  }
  function resetGO(){
    ['go_batch','go_fc','go_prod_qty','go_cost','go_price','go_prod_date','go_exp_date','go_order_date','go_order_qty'].forEach(i=>{ if($(i)) $(i).value=''; });
    if ($('go_product_id')) $('go_product_id').selectedIndex = 0;
  }

  // When product changes: set form action, autofill cost/price, forecast; setup auto expiry
  $('go_product_id')?.addEventListener('change', async (e) => {
    const opt = e.target.selectedOptions[0]; if (!opt) return;
    const id = opt.value, name = opt.dataset.name, shelf = parseInt(opt.dataset.shelf || '7',10);
    const defPrice = parseFloat(opt.dataset.price || '0');

    // form action -> /production/{id}/order
    const form = $('globalOrderForm');
    form.action = `{{ url('/production') }}/${id}/order`;

    // default price
    $('go_price').value = defPrice.toFixed(2);

    // fetch product info (cost, forecast)
    try {
      const res = await fetch(`{{ url('/production/info') }}/${encodeURIComponent(name)}`);
      if (!res.ok) throw new Error('fetch failed');
      const info = await res.json();
      $('go_cost').value = Number(info.unit_cost ?? 0).toFixed(2);
      $('go_fc').value = Number(info.forecasted_demand ?? 0);
    } catch (_) {
      $('go_cost').value = '';
      $('go_fc').value = '';
    }

    // auto expiry
    const prod = $('go_prod_date'), exp = $('go_exp_date');
    const recalc = () => {
      if (!prod.value) return;
      const d = new Date(prod.value); d.setDate(d.getDate() + shelf);
      exp.value = d.toISOString().slice(0,10); exp.min = prod.value;
    };
    prod.removeEventListener('change', recalc); // de-dup
    prod.addEventListener('change', recalc);
    recalc(); // initial
  });
</script>
