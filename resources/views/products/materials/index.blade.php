@extends('layout.mainlayout')
@section('title', 'Recipe / BOM · '.$product->product_name)

@section('styles')
<style>
  .card{background:linear-gradient(180deg,rgba(255,255,255,.04),rgba(255,255,255,.02));
        border:1px solid rgba(255,255,255,.08); border-radius:16px; padding:16px}
  .table-dark th, .table-dark td{border-color:rgba(255,255,255,.08)}
  .w-110{width:110px}
</style>
@endsection

@section('content')
<div class="page-wrap mx-auto max-w-5xl">
  <div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-white">
      Recipe / BOM — {{ $product->product_name }}
    </h1>
    <a href="{{ route('products.show', $product) }}" class="px-3 py-2 rounded bg-gray-700 hover:bg-gray-600 text-white text-sm">
      ← Back to Product
    </a>
  </div>

  {{-- Add row --}}
  <div class="card mb-6">
    <form id="add-line-form" method="post" action="{{ route('products.materials.store', $product) }}">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
          <label class="block text-sm text-gray-300 mb-1">Material</label>
          <select name="rows[0][ingredient_id]" id="mat-select" class="w-full bg-gray-800 text-white rounded p-2">
            <option value="">-- choose --</option>
            @foreach($materials as $m)
              <option value="{{ $m->id }}"
                      data-unit="{{ $m->unit }}"
                      data-price="{{ (float)($m->default_unit_price ?? 0) }}">
                {{ $m->material_name }}
              </option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-sm text-gray-300 mb-1">Qty</label>
          <input type="number" step="0.001" min="0" name="rows[0][qty]" id="qty-input"
                 class="w-full bg-gray-800 text-white rounded p-2" value="1">
        </div>
        <div>
          <label class="block text-sm text-gray-300 mb-1">Unit price</label>
          <input type="number" step="0.01" min="0" name="rows[0][unit_price]" id="price-input"
                 class="w-full bg-gray-800 text-white rounded p-2" value="0">
          <div class="text-xs text-gray-400 mt-1">Auto-fills from material</div>
        </div>
        <div class="flex items-end">
          <button class="w-full bg-lime-500 hover:bg-lime-600 text-black font-medium rounded p-2">
            Add / Save
          </button>
        </div>
      </div>
      <input type="hidden" id="unit-hidden" value="">
    </form>
  </div>

  {{-- Current recipe table --}}
  <div class="card">
    <h2 class="text-lg font-semibold text-white mb-3">Current Lines</h2>
    <div class="overflow-x-auto">
      <table class="table-dark w-full text-sm">
        <thead>
          <tr class="text-gray-300">
            <th class="text-left p-2">Material</th>
            <th class="text-right p-2 w-110">Qty</th>
            <th class="text-left p-2 w-110">Unit</th>
            <th class="text-right p-2 w-110">Unit Price</th>
            <th class="text-right p-2 w-110">Line Total</th>
            <th class="text-right p-2 w-110">Actions</th>
          </tr>
        </thead>
        <tbody>
        @php $grand = 0; @endphp
        @forelse($recipe as $line)
          @php
            $mat = $line->material;
            $unit = $mat->unit ?? '';
            $snap = (float)($line->unit_price_snapshot ?? 0);
            $qty  = (float)($line->qty ?? 0);
            $total = $qty * $snap;
            $grand += $total;
          @endphp
          <tr class="border-t border-gray-700">
            <td class="p-2 text-white">{{ $mat->material_name ?? '—' }}</td>
            <td class="p-2 text-right text-white">{{ number_format($qty, 3) }}</td>
            <td class="p-2 text-white">{{ $unit }}</td>
            <td class="p-2 text-right text-white">{{ number_format($snap, 2) }}</td>
            <td class="p-2 text-right text-white">{{ number_format($total, 2) }}</td>
            <td class="p-2 text-right">
              <form method="post" action="{{ route('products.materials.destroy', [$product, $line]) }}"
                    onsubmit="return confirm('Remove this line?')">
                @csrf
                @method('DELETE')
                <button class="px-2 py-1 rounded bg-red-600 hover:bg-red-700 text-white">Delete</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="p-4 text-gray-300">No lines yet. Add one above.</td>
          </tr>
        @endforelse
        </tbody>
        <tfoot>
          <tr class="border-t border-gray-700">
            <th colspan="4" class="p-2 text-right text-gray-300">Grand Total</th>
            <th class="p-2 text-right text-white">{{ number_format($grand, 2) }}</th>
            <th></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  const sel   = document.getElementById('mat-select');
  const price = document.getElementById('price-input');
  const unitH = document.getElementById('unit-hidden');

  sel.addEventListener('change', () => {
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.dataset) return;
    // auto-fill unit price and keep unit (if you want to display it later)
    price.value = opt.dataset.price || 0;
    unitH.value = opt.dataset.unit || '';
  });
</script>
@endsection
