@extends('layout.mainlayout')

@section('content')
<div class="glass section-liquid-shine text-white p-6 rounded-2xl shadow-md border border-dark-line">

  {{-- Header --}}
  <div class="flex items-center justify-between mb-6">
    <div>
      <h2 id="productTitle" class="text-2xl font-bold tracking-wide">{{ $product->product_name }}</h2>
      <p class="text-sm text-[var(--muted,#A3B4A7)]">
        Category: <span id="productCategory">{{ $product->category ?? 'Uncategorized' }}</span>
      </p>
    </div>
    <img
      id="productImage"
      src="{{ $product->image_url ?? '/images/default-burger.png' }}"
      class="w-24 h-24 object-cover rounded-xl shadow border border-dark-line ring-1 ring-white/10"
      alt="{{ $product->product_name }}"
    >
  </div>

  {{-- Top actions --}}
  <div class="flex justify-between items-center mb-4">
    <a href="{{ route('production.index') }}" class="text-[var(--sidebar-active,#EDD100)] hover:opacity-90">&larr; Back to Production</a>
    <button id="addOrderBtn" type="button"
            class="px-4 py-2 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] font-semibold shadow hover:opacity-90 transition">
      + Add Order
    </button>
  </div>

  {{-- Flash + Errors --}}
  @if(session('success'))
    <div class="mb-4 p-3 rounded-xl bg-emerald-500/15 text-emerald-200 border border-emerald-400/30">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="mb-4 p-3 rounded-xl bg-red-500/15 text-red-200 border border-red-400/30">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
    <div class="mb-4 p-3 rounded-xl bg-red-500/10 text-red-200 border border-red-400/30">
      <ul class="list-disc pl-6">
        @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
      </ul>
    </div>
  @endif

  {{-- Orders Table --}}
  <div class="overflow-x-auto rounded-2xl ring-1 ring-white/10">
    <table class="min-w-full text-sm text-left rounded-2xl overflow-hidden">
      <thead class="bg-white/5 text-white uppercase text-xs">
        <tr>
          <th class="py-3 px-4">Batch #</th>
          <th class="py-3 px-4">Forecasted</th>
          <th class="py-3 px-4">Produced</th>
          <th class="py-3 px-4">Unit Cost</th>
          <th class="py-3 px-4">Prod. Date</th>
          <th class="py-3 px-4">Expiry</th>
          <th class="py-3 px-4">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-white/10">
        @forelse ($orders as $o)
          <tr class="hover:bg-white/5 transition">
            <td class="py-3 px-4 font-mono text-xs">{{ $o->batch_number }}</td>
            <td class="py-3 px-4">{{ (float)$o->forecasted_demand }} kg</td>
            <td class="py-3 px-4">{{ (float)($o->quantity ?? $o->current_inventory) }} kg</td>
            <td class="py-3 px-4">₱{{ number_format((float)$o->unit_cost, 2) }}</td>
            <td class="py-3 px-4">{{ \Carbon\Carbon::parse($o->production_date)->format('M d, Y') }}</td>
            <td class="py-3 px-4">
              {{ $o->expiration_date ? \Carbon\Carbon::parse($o->expiration_date)->format('M d, Y') : '—' }}
            </td>
            <td class="py-3 px-4">
              <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('production.edit', $o->id) }}"
                   class="px-3 py-1.5 rounded-full border border-white/20 hover:border-[var(--brand-green,#047705)] hover:bg-[var(--brand-green,#047705)]/20 transition">
                  Edit
                </a>
                <form action="{{ route('production.destroy', $o->id) }}" method="POST"
                      onsubmit="return confirm('Delete this batch? Inventory will be adjusted.')">
                  @csrf @method('DELETE')
                  <button type="submit"
                          class="px-3 py-1.5 rounded-full border border-red-500/40 text-red-300 hover:bg-red-500/10 transition">
                    Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="py-4 text-center text-[var(--muted,#A3B4A7)]">No production orders yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Add Order Modal --}}
<div id="addOrderModal" class="fixed inset-0 z-[9999] hidden">
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeOrderModal()" aria-hidden="true"></div>

  <div class="relative mx-auto my-10 max-w-md w-[92%] glass border border-dark-line rounded-2xl shadow-xl text-white p-6 animate-fadeIn">
    <button type="button" onclick="closeOrderModal()" aria-label="Close"
            class="absolute top-2 right-4 text-2xl font-bold hover:text-red-400">&times;</button>

    <h3 id="modalTitle" class="text-xl font-semibold mb-4">Add Order ({{ $product->product_name }})</h3>

    <form id="addOrderForm" action="{{ route('production.storeOrder', $product->id) }}" method="POST" class="space-y-3">
      @csrf

      {{-- Product select + quick add --}}
      <div>
        <label class="block text-sm mb-1">Product</label>

        <select id="po_product_select"
                class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400">
          @foreach(($allProducts ?? collect([$product])) as $p)
            <option value="{{ $p->id }}" {{ (int)$p->id === (int)$product->id ? 'selected' : '' }}>
              {{ $p->product_name }}
            </option>
          @endforeach
        </select>

        <div class="mt-2">
          <button type="button" id="po_new_toggle"
                  class="text-xs px-2.5 py-1 rounded-full border border-white/20 hover:border-[var(--brand-green,#047705)] hover:bg-[var(--brand-green,#047705)]/20 transition">
            + New product
          </button>
        </div>

        {{-- inline quick-add form --}}
        <div id="po_new_wrap" class="mt-3 hidden rounded-xl border border-white/10 bg-white/5 p-3">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs mb-1">Product Name</label>
              <input id="po_new_name" type="text" class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" placeholder="e.g., Burger Patty" />
            </div>
            <div>
              <label class="block text-xs mb-1">Category (optional)</label>
              <input id="po_new_cat" type="text" class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" placeholder="e.g., Meat" />
            </div>
            <div>
              <label class="block text-xs mb-1">Unit Cost (₱)</label>
              <input id="po_new_cost" type="number" step="any" min="0" class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" />
            </div>
            <div>
              <label class="block text-xs mb-1">Shelf Life (days)</label>
              <input id="po_new_shelf" type="number" min="0" class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" value="7" />
            </div>
          </div>
          <div class="mt-3 flex items-center gap-2">
            <button type="button" id="po_new_save"
                    class="px-3 py-1.5 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] text-sm font-semibold hover:opacity-90 transition">
              Save product
            </button>
            <button type="button" id="po_new_cancel"
                    class="px-3 py-1.5 rounded-xl border border-white/15 text-sm hover:bg-white/10 transition">
              Cancel
            </button>
            <span id="po_new_err" class="text-xs text-red-300 ml-2 hidden"></span>
          </div>
        </div>
      </div>

      {{-- Batch Number (preview only) --}}
      <div>
        <label class="block text-sm mb-1">Batch Number</label>
        <input id="po_batch_preview"
               class="w-full rounded-xl bg-white/10 border border-white/10 px-3 py-2 text-white/90 cursor-not-allowed"
               value="{{ $nextBatchNumber ?? 'Auto' }}"
               readonly>
      </div>

      {{-- Forecasted / Produced --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Forecasted Demand (kg)</label>
          <input id="po_fc" name="forecasted_demand" type="number" step="any"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                 value="{{ old('forecasted_demand', (float)($product->forecasted_demand ?? 0)) }}">
        </div>
        <div>
          <label class="block text-sm mb-1">Produced Quantity (kg)</label>
          <input id="po_prod_qty" name="produced_qty_kg" type="number" step="any" required
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                 value="{{ old('produced_qty_kg') }}">
        </div>
      </div>

      {{-- Unit Cost / Unit Price --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Unit Cost (₱)</label>
          <input id="po_cost" name="unit_cost" type="number" step="any" required
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                 value="{{ old('unit_cost', (float)($product->unit_cost ?? 0)) }}">
        </div>
        <div>
          <label class="block text-sm mb-1">Unit Price (₱)</label>
          <input id="po_price" name="unit_price" type="number" step="any"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                 value="{{ old('unit_price', (float)($defaultUnitPrice ?? $product->default_price ?? 0)) }}">
          <p class="text-xs text-[var(--muted,#A3B4A7)] mt-1">Leave blank to use latest sale/product price.</p>
        </div>
      </div>

      {{-- Dates --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Production Date</label>
          <input id="po_prod_date" name="production_date" type="date" required
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                 value="{{ old('production_date', $defaultProdDate ?? now()->toDateString()) }}">
        </div>
        <div>
          <label class="block text-sm mb-1">Expiration Date (auto)</label>
          <input id="po_exp_preview" type="date" readonly
                 class="w-full rounded-xl bg-white/10 border border-white/10 px-3 py-2 text-white/90 cursor-not-allowed"
                 value="{{ old('expiration_date', $defaultExpiry ?? '') }}">
          <p class="text-xs text-[var(--muted,#A3B4A7)] mt-1">
            Computed from production date + {{ (int)($product->shelf_life_days ?? 7) }} days.
          </p>
        </div>
      </div>

      {{-- Order fields --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Order Date</label>
          <input id="po_order_date" name="order_date" type="date"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                 value="{{ old('order_date', now()->toDateString()) }}">
          <p class="text-xs text-[var(--muted,#A3B4A7)] mt-1">Leave blank to use today.</p>
        </div>
        <div>
          <label class="block text-sm mb-1">Order Quantity (kg)</label>
          <input id="po_order_qty" name="order_quantity_kg" type="number" step="any" required
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                 value="{{ old('order_quantity_kg') }}">
        </div>
      </div>

      {{-- Optional --}}
      <div>
        <label class="block text-sm mb-1">Customer (optional)</label>
        <input name="customer_name"
               class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
               value="{{ old('customer_name') }}" placeholder="Walk-in, Distributor A, etc.">
      </div>
      <div>
        <label class="block text-sm mb-1">Notes</label>
        <textarea name="notes" rows="2"
                  class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                  placeholder="Special handling, delivery date, etc.">{{ old('notes') }}</textarea>
      </div>

      <button id="submitAddOrder"
              class="w-full mt-2 px-4 py-2 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] font-semibold shadow hover:opacity-90 transition">
        Add Order (Production + Sale)
      </button>
    </form>
  </div>
</div>

{{-- Tiny animation --}}
<style>
@keyframes fadeIn { from{opacity:0;transform:scale(.98)} to{opacity:1;transform:scale(1)} }
.animate-fadeIn{ animation:fadeIn .2s ease-out }
</style>
@endsection

@section('scripts')
<script>
  const $$ = id => document.getElementById(id);

  function openOrderModal(){
    syncExpiryPreview();
    // Seed defaults for the initially-selected product
    const sel = $$('#po_product_select');
    if (sel) {
      const opt = sel.options[sel.selectedIndex];
      updateFormForProduct(opt.value, opt.textContent.trim());
      autoFillCostPriceForName(opt.textContent.trim());
    }
    const m = $$('#addOrderModal');
    if (!m) return;
    m.classList.remove('hidden'); m.classList.add('flex');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  function closeOrderModal(){
    const m = $$('#addOrderModal');
    if (!m) return;
    m.classList.add('hidden'); m.classList.remove('flex');
  }

  document.addEventListener('DOMContentLoaded', () => {
    $$('#addOrderBtn')?.addEventListener('click', openOrderModal);
  });

  // Expiry preview from production date + shelf life
  function syncExpiryPreview(){
    const shelf = {{ (int)($product->shelf_life_days ?? 7) }};
    const prod  = $$('#po_prod_date');
    const expPv = $$('#po_exp_preview');
    if(!prod || !expPv || !prod.value) return;
    const d = new Date(prod.value);
    d.setDate(d.getDate() + shelf);
    expPv.value = d.toISOString().slice(0,10);
    expPv.min   = prod.value;
  }
  document.addEventListener('change', e => {
    if (e.target && e.target.id === 'po_prod_date') syncExpiryPreview();
  });

  // Auto-fill from server for a given product name (only if field is empty)
  function autoFillCostPriceForName(name){
    fetch(`{{ route('production.info', ':name') }}`.replace(':name', encodeURIComponent(name)))
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(info => {
        if ($$('#po_cost')  && !$$('#po_cost').value)  $$('#po_cost').value  = Number(info.unit_cost ?? 0).toFixed(2);
        if ($$('#po_price') && !$$('#po_price').value) $$('#po_price').value = Number(info.default_price ?? 0).toFixed(2);
        if ($$('#po_fc')    && !$$('#po_fc').value)    $$('#po_fc').value    = Number(info.forecasted_demand ?? 0);
      })
      .catch(() => {});
  }

  // Prevent double submit
  (function(){
    const form = $$('#addOrderForm');
    const btn  = $$('#submitAddOrder');
    if (!form || !btn) return;
    form.addEventListener('submit', () => {
      btn.disabled = true;
      btn.textContent = 'Saving...';
    });
  })();

  // ==== Product select + quick add ====

  const csrfToken = () => (document.querySelector('#addOrderForm input[name=_token]')?.value || '');

  function updateFormForProduct(productId, productName){
    // update form action (POST /production/{id}/order)
    const form = document.getElementById('addOrderForm');
    if (form) {
      const base = @json(route('production.storeOrder', ['product' => 'PRODUCT_ID_PLACEHOLDER']));
      form.action = base.replace('PRODUCT_ID_PLACEHOLDER', String(productId));
    }
    // update modal title
    const title = document.getElementById('modalTitle');
    if (title) title.textContent = `Add Order (${productName})`;
  }

  document.addEventListener('DOMContentLoaded', () => {
    const sel   = document.getElementById('po_product_select');
    const wrap  = document.getElementById('po_new_wrap');
    const tog   = document.getElementById('po_new_toggle');
    const save  = document.getElementById('po_new_save');
    const cancel= document.getElementById('po_new_cancel');
    const err   = document.getElementById('po_new_err');

    // change product -> update action/title and fetch defaults
    sel?.addEventListener('change', () => {
      const opt = sel.options[sel.selectedIndex];
      updateFormForProduct(opt.value, opt.textContent.trim());
      // Clear price/cost to allow server defaults to fill if user wants
      // (or keep as-is if you prefer)
      autoFillCostPriceForName(opt.textContent.trim());
    });

    // toggle quick-add
    tog?.addEventListener('click', () => {
      wrap?.classList.toggle('hidden');
      if (err){ err.textContent=''; err.classList.add('hidden'); }
    });
    cancel?.addEventListener('click', () => {
      wrap?.classList.add('hidden');
      if (err){ err.textContent=''; err.classList.add('hidden'); }
    });

    // save new product via AJAX
    save?.addEventListener('click', async () => {
      const name  = document.getElementById('po_new_name')?.value?.trim();
      const cat   = document.getElementById('po_new_cat')?.value?.trim();
      const cost  = parseFloat(document.getElementById('po_new_cost')?.value || '0') || 0;
      const shelf = parseInt(document.getElementById('po_new_shelf')?.value || '7', 10) || 7;

      if (!name) {
        err.textContent = 'Product name is required.'; err.classList.remove('hidden'); return;
      }

      try {
        const res = await fetch(@json(route('products.quick-store')), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            product_name: name,
            category: cat || null,
            unit_cost: cost,
            shelf_life_days: shelf
          })
        });
        if (!res.ok) throw new Error('Failed to create product');
        const data = await res.json(); // { id, product_name, category?, unit_cost?, shelf_life_days? }

        // add to select + select it
        const opt = new Option(data.product_name, data.id, true, true);
        sel.add(opt);
        sel.value = data.id;

        // update form + title
        updateFormForProduct(data.id, data.product_name);

        // seed Unit Cost with just-entered value if empty
        if ($$('#po_cost') && !$$('#po_cost').value && typeof data.unit_cost !== 'undefined') {
          $$('#po_cost').value = Number(data.unit_cost).toFixed(2);
        }

        wrap?.classList.add('hidden');
        err.classList.add('hidden'); err.textContent = '';
      } catch (e) {
        err.textContent = 'Could not save product. Please try again.';
        err.classList.remove('hidden');
      }
    });
  });

  // Auto-open modal on validation errors
  @if ($errors->any())
    window.addEventListener('load', openOrderModal);
  @endif
</script>
@endsection
