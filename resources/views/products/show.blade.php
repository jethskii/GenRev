{{-- resources/views/products/show.blade.php --}}
@extends('layouts.inventory')
@section('title', ($product->product_name ?? 'Product').' · Product')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

  {{-- LEFT: product overview + tabs --}}
  <div class="lg:col-span-2 card rounded-2xl p-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="flex gap-4">
        @php
          $img = $product->image_url ?? '/images/default-burger.png';
          $name = $product->product_name ?? $product->name ?? 'Unnamed Product';
          $category = $product->category ?? '—';
          $shelf = (int)($product->shelf_life_days ?? 0);
          $stockQty = (float)($product->available_stock_kg ?? $product->quantity ?? 0);
          $unit = $product->unit ?? 'kg';
          $status = strtolower($product->status ?? ((($product->quantity ?? 0) > 0) ? 'active' : 'inactive'));
          $badgeMap = [
            'active'   => 'bg-green-500/20 text-green-300',
            'inactive' => 'bg-red-500/20 text-red-300',
            'pending'  => 'bg-amber-500/20 text-amber-200',
            'on_sale'  => 'bg-blue-500/20 text-blue-300',
          ];
          $badgeClass = $badgeMap[$status] ?? 'bg-gray-500/20 text-gray-300';
        @endphp

        <img src="{{ $img }}" alt="{{ $name }}"
             class="w-40 h-40 object-cover rounded-xl border border-white/10">
        <div class="space-y-2">
          <div class="text-xl font-semibold flex items-center gap-2">
            {{ $name }}
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">
              {{ ucfirst($status) }}
            </span>
          </div>
          <div class="text-sm opacity-70">Category: {{ $category }}</div>
          <div class="text-sm">
            Stock:
            {{ number_format($stockQty, $unit === 'pcs' ? 0 : 3) }}
            <span class="opacity-70">{{ $unit }}</span>
          </div>
          <div class="text-sm">Shelf Life: {{ $shelf > 0 ? $shelf.' days' : '—' }}</div>

          {{-- Quick metrics (computed) --}}
          @php
            $unitMaterialCost = (float)($product->unit_material_cost ?? 0);
            $stdCost = (float)($product->unit_cost ?? 0);
          @endphp
          <div class="text-sm">
            Unit Material Cost:
            <span class="font-medium">₱{{ number_format($unitMaterialCost, 2) }}</span>
            @if($stdCost)
              <span class="opacity-70"> • Std Cost: ₱{{ number_format($stdCost,2) }}</span>
            @endif
          </div>
        </div>
      </div>

      {{-- Quick actions --}}
      <div class="flex flex-wrap gap-2">
        <a href="{{ route('products.edit', $product) }}" class="btn-armygreen">Edit Product</a>
        <a href="{{ route('products.materials.index', $product) }}" class="px-3 py-2 rounded-xl border border-white/15 hover:bg-white/5">Manage Materials</a>
        <a href="{{ route('production.orders', $product->id) }}" class="px-3 py-2 rounded-xl border border-white/15 hover:bg-white/5">Go to Production</a>
        @if(Route::has('products.export'))
          <a href="{{ route('products.export', $product) }}" class="px-3 py-2 rounded-xl border border-white/15 hover:bg-white/5">Export</a>
        @endif
      </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
      <div class="mt-4 text-green-400 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="mt-4 text-red-400 text-sm">Please fix the errors and try again.</div>
    @endif

    {{-- Tabs header --}}
    <div class="mt-6">
      <div class="flex flex-wrap gap-2">
        <a href="#tab-batches" class="btn-armygreen">Batches</a>
        <a href="#tab-recipe"  class="btn-armygreen">Recipe</a>
        <a href="#tab-sales"   class="btn-armygreen">Sales</a>
      </div>
    </div>

    {{-- TAB: Batches --}}
    <div id="tab-batches" class="mt-4">
      <h3 class="font-semibold mb-2">Batches</h3>

      <div class="overflow-x-auto rounded-xl border border-white/10">
        <table class="w-full text-sm table-dark border-collapse">
          <thead>
            <tr>
              <th class="p-2 text-left">Batch</th>
              <th class="p-2">Prod</th>
              <th class="p-2">Exp</th>
              <th class="p-2 text-right">Qty</th>
              <th class="p-2">Status</th>
              <th class="p-2 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
          @forelse($product->productions ?? [] as $b)
            @php
              $qty = (float)($b->current_inventory ?? $b->quantity ?? 0);
              $isExpired = (bool)($b->is_expired ?? false);
              $dte = $b->days_to_expiry ?? null;
            @endphp
            <tr class="hover:bg-white/5">
              <td class="p-2">{{ $b->batch_number ?? '—' }}</td>
              <td class="p-2">{{ optional($b->production_date)->format('Y-m-d') ?? '—' }}</td>
              <td class="p-2">{{ optional($b->expiration_date)->format('Y-m-d') ?? '—' }}</td>
              <td class="p-2 text-right">{{ number_format($qty, $unit === 'pcs' ? 0 : 3) }}</td>
              <td class="p-2">
                @if($isExpired)
                  <span class="px-2 py-1 rounded bg-red-600/40 text-red-200 text-xs">Expired</span>
                @elseif(!is_null($dte) && $dte <= 7)
                  <span class="px-2 py-1 rounded bg-amber-600/40 text-amber-200 text-xs">Expiring Soon ({{ $dte }}d)</span>
                @else
                  <span class="px-2 py-1 rounded bg-emerald-600/30 text-emerald-200 text-xs">OK</span>
                @endif
              </td>
              <td class="p-2 text-right">
                @if(Route::has('production.orders.show'))
                  <a href="{{ route('production.orders.show', $b->id) }}" class="text-armygreen hover:underline">View</a>
                @endif
                @if(Route::has('production.orders.destroy'))
                  <form method="POST" action="{{ route('production.orders.destroy', $b->id) }}" class="inline"
                        onsubmit="return confirm('Archive/delete this batch?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-300 hover:underline ml-3">Delete</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="p-6 text-center text-white/60">No batches yet.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- TAB: Recipe --}}
    <div id="tab-recipe" class="mt-8">
      <h3 class="font-semibold mb-2">Recipe (BOM)</h3>

      {{-- Add line --}}
      <form method="POST" action="{{ route('products.recipe.store', $product) }}"
            class="flex gap-2 flex-wrap items-end">
        @csrf
        <div>
          <label class="text-sm block">Material</label>
          <select name="material_id" class="input-dark px-3 py-2 rounded-xl" required>
            @foreach(($materials ?? []) as $m)
              <option value="{{ $m->id }}">{{ $m->material_name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="text-sm block">Qty (per 1 {{ $unit }})</label>
          <input type="number" step="0.001" min="0" name="qty"
                 class="input-dark px-3 py-2 rounded-xl" required>
        </div>
        <button class="btn-armygreen">Add</button>
      </form>

      {{-- Current lines --}}
      @php
        $recipeLines = $recipe ?? collect();
        $totalCost = (float)$recipeLines->sum(fn($l) => (float)($l->unit_price_snapshot ?? 0) * (float)($l->qty ?? 0));
      @endphp

      <div class="mt-3 overflow-x-auto rounded-xl border border-white/10">
        <table class="w-full text-sm table-dark border-collapse">
          <thead>
            <tr>
              <th class="p-2 text-left">Material</th>
              <th class="p-2 text-right">Qty</th>
              <th class="p-2 text-right">Unit Price Snapshot</th>
              <th class="p-2 text-right">Line Cost</th>
              <th class="p-2"></th>
            </tr>
          </thead>
          <tbody>
          @forelse($recipeLines as $line)
            @php
              $qty = (float)($line->qty ?? 0);
              $price = (float)($line->unit_price_snapshot ?? 0);
            @endphp
            <tr class="hover:bg-white/5">
              <td class="p-2">{{ $line->material->material_name ?? '—' }}</td>
              <td class="p-2 text-right">{{ number_format($qty,3) }} <span class="opacity-60">{{ $unit }}</span></td>
              <td class="p-2 text-right">₱{{ number_format($price,2) }}</td>
              <td class="p-2 text-right">₱{{ number_format($qty * $price,2) }}</td>
              <td class="p-2 text-right">
                <form method="POST" action="{{ route('products.recipe.destroy', [$product, $line]) }}"
                      onsubmit="return confirm('Remove this material from recipe?');">
                  @csrf @method('DELETE')
                  <button class="text-red-300 hover:underline">Remove</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="p-6 text-center text-white/60">No recipe lines yet.</td></tr>
          @endforelse
          </tbody>
          @if($recipeLines->count())
            <tfoot>
              <tr>
                <td class="p-2 font-semibold" colspan="3">Total Unit Material Cost</td>
                <td class="p-2 text-right font-semibold">₱{{ number_format($totalCost,2) }}</td>
                <td></td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>

    {{-- TAB: Sales (quick) --}}
    <div id="tab-sales" class="mt-8">
      <h3 class="font-semibold mb-2">Quick Sale</h3>
      <form method="POST" action="{{ route('sales.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <div>
          <label class="text-sm">Date</label>
          <input type="datetime-local" name="date"
                 value="{{ now()->format('Y-m-d\TH:i') }}"
                 class="w-full input-dark rounded-xl px-3 py-2" required>
        </div>
        <div>
          <label class="text-sm">Qty ({{ $unit }})</label>
          <input step="{{ $unit === 'pcs' ? 1 : '0.001' }}" min="{{ $unit === 'pcs' ? 1 : '0.001' }}"
                 type="number" name="quantity"
                 class="w-full input-dark rounded-xl px-3 py-2" required>
        </div>
        <div>
          <label class="text-sm">Unit Price</label>
          <input step="0.01" min="0" type="number" name="price"
                 value="{{ $product->default_price ?? $product->unit_cost ?? '' }}"
                 class="w-full input-dark rounded-xl px-3 py-2" required>
        </div>
        <div class="flex items-end">
          <button class="btn-armygreen w-full">Save + FIFO</button>
        </div>
      </form>
    </div>
  </div>

  {{-- RIGHT: Add Batch --}}
  <div class="card rounded-2xl p-4">
    <h3 class="font-semibold mb-2">Add Batch</h3>
    <form method="POST" action="{{ route('production.orders.store') }}" class="space-y-3">
      @csrf
      <input type="hidden" name="product_id" value="{{ $product->id }}">

      <div>
        <label class="text-sm">Production Date</label>
        <input type="date" name="production_date"
               value="{{ now()->toDateString() }}"
               class="w-full input-dark rounded-xl px-3 py-2" required>
      </div>

      <div>
        <label class="text-sm">Expiration Date</label>
        <input type="date" name="expiration_date"
               value="{{ ($product->shelf_life_days ?? null) ? now()->addDays($product->shelf_life_days)->toDateString() : '' }}"
               class="w-full input-dark rounded-xl px-3 py-2"
               placeholder="{{ $shelf ? '' : 'Auto after save if shelf life set' }}">
      </div>

      <div>
        <label class="text-sm">Quantity ({{ $unit }})</label>
        <input type="number"
               step="{{ $unit === 'pcs' ? 1 : '0.001' }}" min="{{ $unit === 'pcs' ? 1 : '0.001' }}"
               name="quantity" class="w-full input-dark rounded-xl px-3 py-2" required>
      </div>

      <div>
        <label class="text-sm">Unit Cost</label>
        <input type="number" step="0.01" min="0" name="unit_cost"
               value="{{ $product->unit_cost ?? $unitMaterialCost }}"
               class="w-full input-dark rounded-xl px-3 py-2">
      </div>

      <button class="btn-armygreen w-full">Add Batch</button>

      @if(!$shelf)
        <p class="text-xs text-amber-300/90">
          Tip: set <strong>Shelf Life</strong> on the product to auto-compute expiry for future batches.
        </p>
      @endif
    </form>
  </div>
</div>
@endsection
