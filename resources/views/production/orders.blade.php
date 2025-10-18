@extends('layout.mainlayout')

@section('styles')
<style>
  /* ---- Light theme tokens (shared) ---- */
  :root{
    --bg-offwhite:#f7f7f5;
    --ink:#0f172a;
    --muted:#475569;
    --line:#e5e7eb;

    --red:#dc2626;     /* primary */
    --green:#16a34a;   /* secondary */
    --blue:#2563eb;    /* secondary */
  }

  .page-card{
    background:#fff;
    color:var(--ink);
    border:1px solid var(--line);
    border-radius:1rem;
    padding:1.25rem;
    box-shadow:0 1px 2px rgba(0,0,0,.04),0 10px 24px rgba(0,0,0,.05);
  }
  .soft-ring{ border:1px solid var(--line); border-radius:1rem; }

  .label{font-size:.85rem;color:var(--muted);margin-bottom:.35rem;display:block}
  .input, .select, .textarea{
    width:100%; background:#fff; color:var(--ink);
    border:1px solid var(--line); border-radius:.75rem;
    padding:.6rem .8rem; line-height:1.35;
    transition: box-shadow .15s ease, border-color .15s ease;
  }
  .input:focus, .select:focus, .textarea:focus{
    outline:0; border-color:var(--blue); box-shadow:0 0 0 3px rgba(37,99,235,.15);
  }
  .help{font-size:.75rem;color:#64748b}

  .btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:.75rem;padding:.6rem .9rem;font-weight:700;border:1px solid transparent;transition:filter .12s ease}
  .btn:disabled{opacity:.6;cursor:not-allowed}
  .btn-primary{background:var(--red);color:#fff}
  .btn-primary:hover{filter:brightness(.97)}
  .btn-outline{background:#fff;color:var(--ink);border:1px solid var(--line)}
  .btn-outline:hover{filter:brightness(.98)}

  /* table */
  table{border-collapse:separate;border-spacing:0}
  thead th{
    font-size:.72rem;letter-spacing:.02em;text-transform:uppercase;
    color:#334155;background:#fafafa;border-bottom:1px solid var(--line);
  }
  tbody td{border-top:1px solid var(--line)}
  tbody tr:hover{background:#fafafa}
  tfoot th, tfoot td{border-top:2px solid var(--line);background:#fafafa}
</style>
@endsection

@section('content')
<div class="page-card">

  {{-- Header --}}
  <div class="flex items-center justify-between mb-6">
    <div>
      <h2 id="productTitle" class="text-2xl font-bold tracking-wide">{{ $product->product_name }}</h2>
      <p class="text-sm text-[color:var(--muted)]">
        Category: <span id="productCategory">{{ $product->category ?? 'Uncategorized' }}</span>
      </p>
    </div>
    <img
      id="productImage"
      src="{{ $product->image_url ?? '/images/default-burger.png' }}"
      class="w-24 h-24 object-cover rounded-xl border border-[color:var(--line)]"
      alt="{{ $product->product_name }}"
    >
  </div>

  {{-- Top actions --}}
  <div class="flex justify-between items-center mb-4">
    <a href="{{ route('production.index') }}" class="text-[color:var(--blue)] hover:underline">&larr; Back to Production</a>
    <button id="addOrderBtn" type="button" class="btn btn-primary">
      + Add Order
    </button>
  </div>

  {{-- Flash + Errors --}}
  @if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 text-green-800 px-3 py-2">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-3 py-2">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-3 py-2">
      <ul class="list-disc pl-6">
        @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
      </ul>
    </div>
  @endif

  {{-- Orders Table --}}
  <div class="overflow-x-auto soft-ring">
    <table class="min-w-full text-sm text-left rounded-2xl overflow-hidden">
      <thead>
        <tr>
          <th class="py-3 px-4">Batch #</th>
          <th class="py-3 px-4">Forecasted</th>
          <th class="py-3 px-4">Produced</th>
          <th class="py-3 px-4">Unit Cost</th>
          <th class="py-3 px-4">Price/Pack</th>
          <th class="py-3 px-4">Price/Bag</th>
          <th class="py-3 px-4">Prod. Date</th>
          <th class="py-3 px-4">Expiry</th>
          <th class="py-3 px-4">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($orders as $o)
          <tr id="order-row-{{ $o->id }}">
            <td class="py-3 px-4 font-mono text-xs">{{ $o->batch_number }}</td>
            <td class="py-3 px-4">{{ number_format((float)$o->forecasted_demand, 3) }} kg</td>
            <td class="py-3 px-4">{{ number_format((float)($o->quantity ?? $o->current_inventory), 3) }} kg</td>
            <td class="py-3 px-4">₱{{ number_format((float)$o->unit_cost, 2) }}</td>
            <td class="py-3 px-4">₱{{ number_format((float)($o->unit_price_pack ?? 0), 2) }}</td>
            <td class="py-3 px-4">₱{{ number_format((float)($o->unit_price_bag  ?? 0), 2) }}</td>
            <td class="py-3 px-4">{{ \Carbon\Carbon::parse($o->production_date)->format('M d, Y') }}</td>
            <td class="py-3 px-4">
              {{ $o->expiration_date ? \Carbon\Carbon::parse($o->expiration_date)->format('M d, Y') : '—' }}
            </td>
            <td class="py-3 px-4">
              <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('production.edit', $o->id) }}" class="btn btn-outline">Edit</a>
                <form action="{{ route('production.destroy', $o->id) }}" method="POST"
                      onsubmit="return confirm('Delete this batch? Inventory will be adjusted.')">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-primary" style="background:var(--red)">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="9" class="py-4 text-center text-[color:var(--muted)]">No production orders yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Add Order Modal (updated) --}}
<div id="addOrderModal" class="fixed inset-0 z-[9999] hidden">
  <div class="absolute inset-0 bg-black/40" onclick="closeOrderModal()" aria-hidden="true"></div>

  <div class="relative mx-auto my-10 max-w-md w-[92%] page-card animate-fadeIn">
    <button type="button" onclick="closeOrderModal()" aria-label="Close"
            class="absolute top-2 right-4 text-2xl font-bold text-[color:var(--muted)] hover:text-[color:var(--red)]">&times;</button>

    <h3 id="modalTitle" class="text-xl font-semibold mb-4">Add Order ({{ $product->product_name }})</h3>

    {{-- Controller expects POST to production.orders.store --}}
    <form id="addOrderForm" action="{{ route('production.orders.store') }}" method="POST" class="space-y-3">
      @csrf
      <input type="hidden" id="po_product_id" name="product_id" value="{{ (int) $product->id }}">
      {{-- Hidden field that mirrors the readonly preview so expiration_date is submitted --}}
      <input type="hidden" id="po_expiration_date" name="expiration_date" value="{{ old('expiration_date', $defaultExpiry ?? '') }}">

      {{-- Product select + quick add --}}
      <div>
        <label class="label">Product</label>
        <select id="po_product_select" class="select">
          @foreach(($allProducts ?? collect([$product])) as $p)
            <option value="{{ $p->id }}" {{ (int)$p->id === (int)$product->id ? 'selected' : '' }}>
              {{ $p->product_name }}
            </option>
          @endforeach
        </select>

        <div class="mt-2">
          <button type="button" id="po_new_toggle" class="btn btn-outline px-2 py-1 text-sm">+ New product</button>
        </div>

        {{-- inline quick-add form --}}
        <div id="po_new_wrap" class="mt-3 hidden rounded-xl border border-[color:var(--line)] bg-white p-3">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="label">Product Name</label>
              <input id="po_new_name" type="text" class="input" placeholder="e.g., Burger Patty" />
            </div>
            <div>
              <label class="label">Category (optional)</label>
              <input id="po_new_cat" type="text" class="input" placeholder="e.g., Meat" />
            </div>
            <div>
              <label class="label">Unit Cost (₱)</label>
              <input id="po_new_cost" type="number" step="0.01" min="0" class="input" />
            </div>
            <div>
              <label class="label">Shelf Life (days)</label>
              <input id="po_new_shelf" type="number" min="1" class="input" value="7" />
            </div>
          </div>
          <div class="mt-3 flex items-center gap-2">
            <button type="button" id="po_new_save" class="btn btn-primary text-sm">Save product</button>
            <button type="button" id="po_new_cancel" class="btn btn-outline text-sm">Cancel</button>
            <span id="po_new_err" class="text-xs text-[color:var(--red)] ml-2 hidden"></span>
          </div>
        </div>
      </div>

      {{-- Batch Number (preview only) --}}
      <div>
        <label class="label">Batch Number</label>
        <input id="po_batch_preview" class="input cursor-not-allowed bg-gray-50" value="{{ $nextBatchNumber ?? 'Auto' }}" readonly>
      </div>

      {{-- Forecasted / Produced --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="label">Forecasted Demand (kg)</label>
          <input id="po_fc" name="forecasted_demand" type="number" step="0.01" min="0" class="input"
                 value="{{ old('forecasted_demand', (float)($product->forecasted_demand ?? 0)) }}">
        </div>
        <div>
          <label class="label">Produced Quantity (kg)</label>
          {{-- Controller validate: quantity --}}
          <input id="po_prod_qty" name="quantity" type="number" step="1" min="1" required class="input"
                 value="{{ old('quantity') }}">
        </div>
      </div>

      {{-- Unit Cost / Prices --}}
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label class="label">Unit Cost (₱)</label>
          <input id="po_cost" name="unit_cost" type="number" step="0.01" min="0" required class="input"
                 value="{{ old('unit_cost', (float)($product->unit_cost ?? 0)) }}">
        </div>
        <div>
          <label class="label">Price per Pack (₱)</label>
          <input id="po_price_pack" name="unit_price_pack" type="number" step="0.01" min="0" class="input"
                 value="{{ old('unit_price_pack') }}">
        </div>
        <div>
          <label class="label">Price per Bag (₱)</label>
          <input id="po_price_bag" name="unit_price_bag" type="number" step="0.01" min="0" class="input"
                 value="{{ old('unit_price_bag') }}">
        </div>
      </div>

      {{-- Dates --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="label">Production Date</label>
          <input id="po_prod_date" name="production_date" type="date" required class="input"
                 value="{{ old('production_date', $defaultProdDate ?? now()->toDateString()) }}">
        </div>
        <div>
          <label class="label">Expiration Date (auto)</label>
          <input id="po_exp_preview" type="date" readonly class="input cursor-not-allowed bg-gray-50"
                 value="{{ old('expiration_date', $defaultExpiry ?? '') }}">
          <p class="help mt-1">Computed from production date + {{ (int)($product->shelf_life_days ?? 7) }} days.</p>
        </div>
      </div>

      {{-- Optional fields --}}
      <div>
        <label class="label">Order Date</label>
        <input id="po_order_date" name="order_date" type="date" class="input"
               value="{{ old('order_date', now()->toDateString()) }}">
        <p class="help mt-1">Leave blank to use today.</p>
      </div>
      <div>
        <label class="label">Order Quantity (kg)</label>
        <input id="po_order_qty" name="order_quantity_kg" type="number" step="0.01" class="input"
               value="{{ old('order_quantity_kg') }}">
      </div>
      <div>
        <label class="label">Customer (optional)</label>
        <input name="customer_name" class="input" value="{{ old('customer_name') }}" placeholder="Walk-in, Distributor A, etc.">
      </div>
      <div>
        <label class="label">Notes</label>
        <textarea name="notes" rows="2" class="textarea" placeholder="Special handling, delivery date, etc.">{{ old('notes') }}</textarea>
      </div>

      <button id="submitAddOrder" class="btn btn-primary w-full">Add Order (Production + Sale)</button>
    </form>
  </div>
</div>

{{-- Tiny animation --}}
<style>
@keyframes fadeIn { from{opacity:0;transform:scale(.98)} to{opacity:1;transform:scale(1)} }
.animate-fadeIn{ animation:fadeIn .18s ease-out }
</style>
@endsection

@section('scripts')
<script>
  const $$ = id => document.getElementById(id);

  function openOrderModal(){
    syncExpiryPreview();
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

  // Expiry preview from production date + shelf life (also keeps hidden name=expiration_date in sync)
  function syncExpiryPreview(){
    const shelf = {{ (int)($product->shelf_life_days ?? 7) }};
    const prod  = $$('#po_prod_date');
    const expPv = $$('#po_exp_preview');
    const expHidden = $$('#po_expiration_date');
    if(!prod || !expPv || !prod.value) return;
    const d = new Date(prod.value);
    d.setDate(d.getDate() + shelf);
    const iso = d.toISOString().slice(0,10);
    expPv.value = iso;
    expPv.min   = prod.value;
    if (expHidden) expHidden.value = iso;
  }
  document.addEventListener('change', e => {
    if (e.target && e.target.id === 'po_prod_date') syncExpiryPreview();
  });

  // Auto-fill from server for a given product name (only if empty)
  function autoFillCostPriceForName(name){
    fetch(`{{ route('production.info', ':name') }}`.replace(':name', encodeURIComponent(name)))
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(info => {
        if ($$('#po_cost')  && !$$('#po_cost').value)  $$('#po_cost').value  = Number(info.unit_cost ?? 0).toFixed(2);
        if ($$('#po_fc')    && !$$('#po_fc').value)    $$('#po_fc').value    = Number(info.forecasted_demand ?? 0);
        // If you later add per-pack/bag defaults to Product, seed them here
        // Example:
        // if ($$('#po_price_pack') && !$$('#po_price_pack').value) $$('#po_price_pack').value = Number(info.default_price_pack ?? 0).toFixed(2);
        // if ($$('#po_price_bag')  && !$$('#po_price_bag').value)  $$('#po_price_bag').value  = Number(info.default_price_bag  ?? 0).toFixed(2);
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
    const hid = $$('#po_product_id');
    if (hid) hid.value = String(productId);
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

    sel?.addEventListener('change', () => {
      const opt = sel.options[sel.selectedIndex];
      updateFormForProduct(opt.value, opt.textContent.trim());
      autoFillCostPriceForName(opt.textContent.trim());
    });

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
        const data = await res.json();

        const opt = new Option(data.product_name, data.id, true, true);
        sel.add(opt);
        sel.value = data.id;

        updateFormForProduct(data.id, data.product_name);

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
