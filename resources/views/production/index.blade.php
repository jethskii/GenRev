@extends('layout.mainlayout')

@section('content')
<div class="bg-white text-gray-900 p-6 rounded-2xl shadow-sm border border-gray-200">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-2">
        <h2 class="text-2xl font-bold tracking-wide">Production Overview</h2>

        <div class="flex items-center gap-3">
            {{-- Global Materials Mode Badge (index-level) --}}
            @if(!$consumeMaterials)
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium
                             bg-gray-100 text-gray-700 border border-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
                    </svg>
                    Materials: Off
                </span>
            @else
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium
                             bg-green-100 text-green-700 border border-green-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 0 1 0 1.414l-7.364 7.364a1 1 0 0 1-1.414 0L3.293 9.435a1 1 0 1 1 1.414-1.414l3.071 3.07 6.657-6.657a1 1 0 0 1 1.272-.141z" clip-rule="evenodd" />
                    </svg>
                    Materials: On
                </span>
            @endif

            {{-- Primary action: red --}}
            <button onclick="openAddModal()" class="btn btn-primary">
                + Add Production
            </button>
        </div>
    </div>

    <div class="mb-6 text-xs text-gray-600">
        @if(!$consumeMaterials)
            Materials won’t be deducted for any order. You can enable this later by setting <code>CONSUME_MATERIALS=true</code>.
        @else
            Materials will be deducted based on each product’s recipe (if present).
        @endif
    </div>

    {{-- Category Filter --}}
    <div class="flex flex-wrap gap-3 mb-6" id="category-buttons">
        @foreach ($categories as $category)
            <button
                class="px-4 py-2 rounded-full bg-gray-100 text-gray-800 border border-gray-200 hover:bg-gray-200 transition category-btn"
                data-category="{{ $category }}"
                type="button"
            >
                {{ $category }}
            </button>
        @endforeach
        @if(count($categories))
            <button type="button" class="text-sm text-gray-600 underline ml-2 clear-filter">Clear Filter</button>
        @endif
    </div>

    {{-- Search + Sort --}}
    <div class="mb-6">
        <form method="GET" action="{{ route('production.index') }}" class="flex flex-wrap gap-2 items-center" id="filtersForm">
            <input
                type="text"
                name="search"
                placeholder="Search product name..."
                value="{{ request('search') }}"
                class="w-full sm:w-1/3 rounded-xl bg-white border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300"
            >
            <select name="sort" id="sort-select"
                    class="rounded-xl bg-white border border-gray-300 px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-300">
                @php $currentSort = request('sort', $sort ?? 'urgency'); @endphp
                <option value="urgency" {{ $currentSort === 'urgency' ? 'selected' : '' }}>Urgency (Low Stock First)</option>
                <option value="expiry"  {{ $currentSort === 'expiry'  ? 'selected' : '' }}>Soonest Expiry</option>
                <option value="name"    {{ $currentSort === 'name'    ? 'selected' : '' }}>Name A–Z</option>
            </select>

            {{-- Secondary action: blue --}}
            <button type="submit" class="btn btn-secondary-blue">Apply</button>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-2xl p-4 border border-gray-200 shadow-sm">
            <p class="text-sm text-gray-500">Forecasted Demand</p>
            <h3 id="sum-forecast" class="text-lg font-bold text-gray-900">{{ number_format((float)$forecastedDemand, 3) }} kg</h3>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-gray-200 shadow-sm">
            <p class="text-sm text-gray-500">Current Inventory</p>
            <h3 id="sum-inventory" class="text-lg font-bold text-gray-900">{{ number_format((float)$actualInventory, 3) }} kg</h3>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-gray-200 shadow-sm">
            <p class="text-sm text-gray-500">Shortfall</p>
            <h3 id="sum-shortfall" class="text-lg font-bold text-red-600">{{ number_format((float)$shortfall, 3) }} kg</h3>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-gray-200 shadow-sm">
            <p class="text-sm text-gray-500">Recommended Production</p>
            <h3 id="sum-recommended" class="text-lg font-bold text-green-600">{{ number_format((float)$recommendedProduction, 3) }} kg</h3>
        </div>
    </div>

    {{-- Product cards --}}
    <div id="product-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @include('production.partials.product-cards', ['products' => $products])
    </div>
</div>

{{-- Add Production Modal (updated) --}}
<div id="addModal" class="fixed inset-0 z-40 hidden items-center justify-center bg-black/40" role="dialog" aria-modal="true" aria-labelledby="addProdTitle">
  <div class="w-full max-w-2xl mx-4 rounded-2xl overflow-hidden border border-gray-200 bg-white shadow-lg">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
      <h3 id="addProdTitle" class="text-gray-900 font-semibold text-lg">Add Production</h3>
      <button type="button" class="text-gray-500 hover:text-gray-700" onclick="closeAddModal()" aria-label="Close">✕</button>
    </div>

    <form id="ajaxProdForm" action="{{ route('production.store') }}" method="POST" enctype="multipart/form-data" class="px-5 py-4 space-y-4">
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
          <label class="block text-sm text-gray-600 mb-1" for="product_id">Product</label>

          {{-- existing product --}}
          <select id="product_id" name="product_id" class="w-full rounded-xl bg-white border border-gray-300 px-3 py-2">
            <option value="">— Select product —</option>
            @foreach ($allProducts as $p)
              <option value="{{ $p->id }}">{{ $p->product_name }}</option>
            @endforeach
          </select>

          {{-- new product --}}
          <input type="text" id="product_name" name="product_name" class="hidden w-full mt-2 rounded-xl bg-white border border-gray-300 px-3 py-2" placeholder="Or enter new product name" autocomplete="off">

          <button type="button" id="toggleNewBtn" class="text-xs text-blue-700 mt-1" aria-expanded="false">+ Add new product</button>
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-1" for="category">Type of Product</label>
          <input type="text" id="category" name="category" class="w-full rounded-xl bg-white border border-gray-300 px-3 py-2" placeholder="e.g., Pork, Beef" autocomplete="off">
        </div>
      </div>

      {{-- Shelf life only matters when creating a new product --}}
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 new-only hidden" id="shelfRow">
        <div>
          <label class="block text-sm text-gray-600 mb-1" for="shelf_life_days">Shelf Life (days)</label>
          <input type="number" min="1" max="365" id="shelf_life_days" name="shelf_life_days" value="7" class="w-full rounded-xl bg-white border border-gray-300 px-3 py-2">
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="block text-sm text-gray-600 mb-1" for="batch_number">Batch Number</label>
          <input type="text" id="batch_number" name="batch_number" class="w-full rounded-xl bg-white border border-gray-300 px-3 py-2" placeholder="Auto if empty" autocomplete="off">
        </div>
        <div>
          <label class="block text-sm text-gray-600 mb-1" for="production_date">Production Date</label>
          <input type="date" id="production_date" name="production_date" class="w-full rounded-xl bg-white border border-gray-300 px-3 py-2" value="{{ now()->toDateString() }}" required>
        </div>
        <div>
          <label class="block text-sm text-gray-600 mb-1" for="expiration_date">Expiration (optional)</label>
          <input type="date" id="expiration_date" name="expiration_date" class="w-full rounded-xl bg-white border border-gray-300 px-3 py-2">
        </div>
      </div>

      {{-- Forecast only (removed Produced Qty & Unit Cost) --}}
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="block text-sm text-gray-600 mb-1" for="forecasted_demand">Forecasted Demand</label>
          <input type="number" step="0.01" min="0" id="forecasted_demand" name="forecasted_demand" class="w-full rounded-xl bg-white border border-gray-300 px-3 py-2">
        </div>
      </div>

      {{-- Prices per pack/bag + Availability (accented via utilities, no <style>) --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        {{-- Pack (yellow) --}}
        <div class="rounded-xl p-3 border border-yellow-300/60 shadow-[inset_0_0_0_1px_rgba(234,179,8,0.18)]">
          <div class="flex items-center justify-between mb-2">
            <label class="block text-sm text-gray-700 m-0">Per Pack</label>
            <span class="inline-flex items-center font-bold text-xs rounded-md px-2 py-0.5 bg-yellow-400 text-gray-900 select-none">PACK</span>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-xs text-gray-500 mb-1" for="unit_price_pack">Price per Pack (₱)</label>
              <input type="number" step="0.01" min="0" id="unit_price_pack" name="unit_price_pack"
                     class="w-full rounded-xl bg-white border border-gray-300 px-3 py-2" placeholder="0.00">
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1" for="available_pack">Available Packs</label>
              <input type="number" step="1" min="0" id="available_pack" name="available_pack"
                     class="w-full rounded-xl bg-white border border-gray-300 px-3 py-2 text-center font-semibold" placeholder="Qty" value="0">
            </div>
          </div>
        </div>

        {{-- Bag (red) --}}
        <div class="rounded-xl p-3 border border-red-400/60 shadow-[inset_0_0_0_1px_rgba(248,113,113,0.18)]">
          <div class="flex items-center justify-between mb-2">
            <label class="block text-sm text-gray-700 m-0">Per Bag</label>
            <span class="inline-flex items-center font-bold text-xs rounded-md px-2 py-0.5 bg-red-600 text-white select-none">BAG</span>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-xs text-gray-500 mb-1" for="unit_price_bag">Price per Bag (₱)</label>
              <input type="number" step="0.01" min="0" id="unit_price_bag" name="unit_price_bag"
                     class="w-full rounded-xl bg-white border border-gray-300 px-3 py-2" placeholder="0.00">
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1" for="available_bag">Available Bags</label>
              <input type="number" step="1" min="0" id="available_bag" name="available_bag"
                     class="w-full rounded-xl bg-white border border-gray-300 px-3 py-2 text-center font-semibold" placeholder="Qty" value="0">
            </div>
          </div>
        </div>
      </div>

      {{-- Remarks --}}
      <div>
        <label for="remarks" class="block text-sm text-gray-600 mb-1">Remarks (optional)</label>
        <textarea id="remarks" name="remarks" rows="3" maxlength="500"
                  class="w-full rounded-xl bg-white border border-gray-300 px-3 py-2"
                  placeholder="Any internal notes about this batch"></textarea>
        <div class="mt-1 text-[11px] text-gray-500">
          <span id="remarksCount">0</span>/500 characters
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" class="btn btn-ghost" onclick="closeAddModal()">Cancel</button>
        {{-- Primary confirmation: red --}}
        <button id="ajaxSubmitBtn" type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

{{-- Sales Quick-Add Modal (unchanged include) --}}
@includeIf('sales.partials.sale-modal')

{{-- Scripts --}}
<script>
(function(){
  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('addModal');
    const form  = document.getElementById('ajaxProdForm');
    const submitBtn = document.getElementById('ajaxSubmitBtn');

    const productIdSel = document.getElementById('product_id');
    const productName  = document.getElementById('product_name');
    const toggleNewBtn = document.getElementById('toggleNewBtn');
    const shelfRow     = document.getElementById('shelfRow');
    const batchInput   = document.getElementById('batch_number');

    // image elements
    const imageInput   = document.getElementById('image');
    const previewWrap  = document.getElementById('imagePreview');
    const previewImg   = document.getElementById('imagePreviewImg');
    const imageMeta    = document.getElementById('imageMeta');
    const clearBtn     = document.getElementById('clearImageBtn');

    const MAX_MB = 4;
    const MIN_W  = 300;
    const MIN_H  = 300;

    const totals = {
      forecast: document.getElementById('sum-forecast'),
      inventory: document.getElementById('sum-inventory'),
      shortfall: document.getElementById('sum-shortfall'),
      recommended: document.getElementById('sum-recommended'),
    };

    window.openAddModal = () => {
        resetModalFields();
        ensureBatchNumber();
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
    };
    window.closeAddModal = () => {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    };

    function ensureBatchNumber() {
        if (!batchInput) return;
        if (batchInput.value && batchInput.value.trim().length > 0) return;
        const now = new Date();
        const pad = n => n.toString().padStart(2,'0');
        batchInput.value = `B-${now.getFullYear()}${pad(now.getMonth()+1)}${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
    }

    function resetModalFields() {
        form.reset();
        hidePreview();
        productIdSel.classList.remove('hidden');
        productName.classList.add('hidden');
        shelfRow.classList.add('hidden');
        toggleNewBtn.textContent = '+ Add new product';
        toggleNewBtn.setAttribute('aria-expanded', 'false');
        if (batchInput) batchInput.value = '';
        // init remarks counter after reset
        updateRemarksCount();
    }

    toggleNewBtn.addEventListener('click', () => {
        productIdSel.classList.toggle('hidden');
        productName.classList.toggle('hidden');
        shelfRow.classList.toggle('hidden');
        const expanded = !productName.classList.contains('hidden');
        toggleNewBtn.textContent = expanded ? 'Use existing product' : '+ Add new product';
        toggleNewBtn.setAttribute('aria-expanded', String(expanded));
        if (expanded) {
          productIdSel.value = '';
          productName.focus();
        } else {
          productName.value = '';
        }
    });

    function hidePreview(){
      previewWrap?.classList.add('hidden');
      if (previewImg) previewImg.src = '#';
      if (imageMeta) imageMeta.textContent = '';
    }
    function bytesToMB(b){ return (b / (1024*1024)).toFixed(2); }

    imageInput?.addEventListener('change', () => {
      const f = imageInput.files?.[0];
      if (!f) { hidePreview(); return; }

      const validTypes = ['image/jpeg','image/png','image/webp'];
      if (!validTypes.includes(f.type)){
        toast('Only JPG, PNG, or WebP allowed.', 'error');
        imageInput.value = '';
        hidePreview();
        return;
      }
      if (f.size > MAX_MB * 1024 * 1024){
        toast(`Image too large. Max ${MAX_MB}MB.`, 'error');
        imageInput.value = '';
        hidePreview();
        return;
      }

      const url = URL.createObjectURL(f);
      previewImg.onload = () => {
        const w = previewImg.naturalWidth;
        const h = previewImg.naturalHeight;
        if (w < MIN_W || h < MIN_H){
          toast(`Image too small. Min ${MIN_W}×${MIN_H}.`, 'error');
          imageInput.value = '';
          hidePreview();
          URL.revokeObjectURL(url);
          return;
        }
        imageMeta.textContent = `${f.name} • ${bytesToMB(f.size)} MB • ${w}×${h}`;
        previewWrap.classList.remove('hidden');
      };
      previewImg.src = url;
    });

    clearBtn?.addEventListener('click', () => {
      imageInput.value = '';
      hidePreview();
    });

    // Remarks counter
    const remarks = document.getElementById('remarks');
    const remarksCount = document.getElementById('remarksCount');
    function updateRemarksCount(){
      if (remarks && remarksCount) remarksCount.textContent = String(remarks.value.length || 0);
    }
    remarks?.addEventListener('input', updateRemarksCount);
    updateRemarksCount();

    // AJAX submit
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-70','cursor-not-allowed');

        const url = form.getAttribute('action');
        const fd  = new FormData(form);

        if (!fd.get('product_id') && !fd.get('product_name')) {
            toast('Please select a product or enter a new name.', 'error');
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-70','cursor-not-allowed');
            return;
        }

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd
            });

            if (!res.ok) {
                if (res.status === 422) {
                    const j = await res.json().catch(()=>({}));
                    const msg = j?.errors ? Object.values(j.errors).flat().join('\n') : 'Validation error';
                    toast(msg, 'error');
                } else {
                    const txt = await res.text();
                    const snippet = (txt||'').replace(/<[^>]*>/g,'').slice(0,200);
                    const label =
                      res.status === 419 ? 'CSRF/session expired' :
                      res.status === 409 ? 'Business rule' :
                      res.status === 413 ? 'Upload too large' :
                      res.status === 500 ? 'Server error' :
                      res.status === 302 ? 'Redirected (auth?)' :
                      `HTTP ${res.status}`;
                    toast(`${label}${snippet ? `\n${snippet}` : ''}`, 'error');
                }
                return;
            }

            const j = await res.json();
            if (!j.ok) { toast(j.message || 'Unable to save.', 'error'); return; }

            upsertProductCard(j.product_id, j.card_html);
            refreshTotals(j.totals);
            toast(j.message || 'Saved.', 'success');
            closeAddModal();
            resetModalFields();
        } catch (err) {
            console.error(err);
            toast('Network error. Please try again.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-70','cursor-not-allowed');
        }
    });

    function upsertProductCard(productId, html) {
        const container = document.getElementById('product-container');
        if (!container) return;

        const existing = document.getElementById(`product-card-${productId}`);
        if (existing) {
            existing.outerHTML = html;
        } else {
            const temp = document.createElement('div');
            temp.innerHTML = html.trim();
            const node = temp.firstElementChild;
            container.prepend(node);
        }
    }

    function refreshTotals(t) {
        if (!t) return;
        const nf = (n) => Number(n || 0).toFixed(3);

        if (totals.forecast)    totals.forecast.textContent    = `${nf(t.forecastedDemand)} kg`;
        if (totals.inventory)   totals.inventory.textContent   = `${nf(t.actualInventory)} kg`;
        if (totals.shortfall)   totals.shortfall.textContent   = `${nf(t.shortfall)} kg`;
        if (totals.recommended) totals.recommended.textContent = `${nf(t.recommendedProduction)} kg`;
    }

    function toast(message, type='info') {
        const el = document.createElement('div');
        el.className = `fixed bottom-6 left-1/2 -translate-x-1/2 px-4 py-2 rounded-xl text-sm shadow z-[9999]
                        ${type==='success' ? 'bg-green-600 text-white' : type==='error' ? 'bg-red-600 text-white' : 'bg-gray-900 text-white'}`;
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(() => { el.remove(); }, 3000);
    }

    // Category filter (keeps current sort)
    const sortSelect = document.getElementById('sort-select');
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const category = btn.dataset.category;
            const sort = sortSelect ? sortSelect.value : 'urgency';
            fetch(`{{ route('production.filter') }}?category=${encodeURIComponent(category)}&sort=${encodeURIComponent(sort) }`)
                .then(res => res.json())
                .then(data => { document.getElementById('product-container').innerHTML = data.html; })
                .catch(console.error);
        });
    });
    const clear = document.querySelector('.clear-filter');
    if (clear) clear.addEventListener('click', () => {
        const sort = sortSelect ? sortSelect.value : 'urgency';
        fetch(`{{ route('production.filter') }}?sort=${encodeURIComponent(sort) }`)
            .then(res => res.json())
            .then(data => { document.getElementById('product-container').innerHTML = data.html; })
            .catch(console.error);
    });

    /* Quick Add (Sales) */
    const endpointFor = (id) => `/production/quick-add/${id}`;
    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('.js-quick-add');
      if (!btn) return;
      const id = Number(btn.dataset.id || 0);
      if (!id) return;

      const originalHTML = btn.innerHTML;
      btn.disabled = true;
      btn.classList.add('opacity-70','cursor-not-allowed');
      btn.innerHTML = 'Loading…';

      try {
        const res = await fetch(endpointFor(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        const payload = { id: data.id ?? id, name: (data.name || '').toString(), price: Number(data.price ?? 0) };
        if (typeof window.prefillSaleModal === 'function') window.prefillSaleModal(payload);
        else toast('Sales modal not available on this page.', 'error');
      } catch (err) {
        console.warn('Quick Add error:', err);
        toast('Could not load Quick Add data.', 'error');
      } finally {
        btn.disabled = false;
        btn.classList.remove('opacity-70','cursor-not-allowed');
        btn.innerHTML = originalHTML;
      }
    }, true);

  });
})();
</script>
@endsection
