@extends('layout.mainlayout')
@section('title', 'Recipe / BOM · '.$product->product_name)

@section('styles')
<style>
  /* ---- Light theme tokens (align with your Settings/Login screens) ---- */
  :root{
    --bg-offwhite:#f7f7f5;
    --ink:#0f172a;
    --muted:#475569;
    --line:#e5e7eb;

    --red:#dc2626;     /* primary buttons */
    --green:#16a34a;   /* secondary */
    --blue:#2563eb;    /* secondary */
  }

  .page-wrap{
    min-height:100%;
    background:
      radial-gradient(1100px 700px at 0% -20%, rgba(220,38,38,.06), transparent 60%),
      radial-gradient(900px 600px at 120% 120%, rgba(37,99,235,.06), transparent 60%),
      var(--bg-offwhite);
    padding: 2rem 1rem 3rem;
    color: var(--ink);
    font-family: 'Inria Sans', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
  }

  .card{
    background:#fff;
    border:1px solid var(--line);
    border-radius: 1rem;
    padding: 1rem;
    box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 10px 24px rgba(0,0,0,.05);
  }
  .card + .card{ margin-top: 1rem; }

  .label{font-size:.85rem; color:var(--muted); margin-bottom:.35rem; display:block}
  .input, .select{
    width:100%;
    background:#fff; color:var(--ink);
    border:1px solid var(--line); border-radius:.75rem;
    padding:.6rem .8rem; line-height:1.35;
    transition: box-shadow .15s ease, border-color .15s ease;
  }
  .input:focus, .select:focus{outline:0; border-color:var(--blue); box-shadow:0 0 0 3px rgba(37,99,235,.15)}
  .help{ font-size:.75rem; color:#64748b }

  .btn{display:inline-flex; align-items:center; justify-content:center; gap:.5rem; border-radius:.75rem; padding:.6rem .9rem; font-weight:700; border:1px solid transparent; transition:filter .12s ease}
  .btn:disabled{opacity:.6; cursor:not-allowed}
  .btn-primary{ background:var(--red); color:#fff }
  .btn-primary:hover{ filter:brightness(.97) }

  .btn-muted{
    background:#fff; color:var(--ink);
    border:1px solid var(--line);
  }
  .btn-muted:hover{ filter:brightness(.98) }

  /* table */
  table{ border-collapse: separate; border-spacing:0; width:100%; }
  thead th{
    font-size:.75rem; letter-spacing:.02em; text-transform:uppercase;
    color:#334155; background:#fafafa; border-bottom:1px solid var(--line);
  }
  tbody td{ border-top:1px solid var(--line) }
  tfoot th, tfoot td{ border-top:2px solid var(--line); background:#fafafa; }

  .w-110{ width:110px }
</style>
@endsection

@section('content')
<div class="page-wrap mx-auto max-w-5xl">

  {{-- Header --}}
  <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-semibold tracking-wide">
      Recipe / BOM — {{ $product->product_name }}
    </h1>

    <a href="{{ route('products.show', $product) }}"
       class="btn btn-muted">
      ← Back to Product
    </a>
  </div>

  {{-- Add line --}}
  <div class="card">
    <form id="add-line-form" method="POST" action="{{ route('products.materials.store', $product) }}">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
          <label class="label">Material</label>
          <select name="rows[0][ingredient_id]" id="mat-select" class="select" required>
            <option value="">— choose —</option>
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
          <label class="label">Qty</label>
          <input type="number" step="0.001" min="0" name="rows[0][qty]" id="qty-input" value="1" class="input" required>
        </div>

        <div>
          <label class="label">Unit price</label>
          <input type="number" step="0.01" min="0" name="rows[0][unit_price]" id="price-input" value="0" class="input" required>
          <div class="help mt-1">Auto-fills from material</div>
        </div>

        <div class="flex items-end">
          <button class="btn btn-primary w-full">Add / Save</button>
        </div>
      </div>

      {{-- Keep selected unit for server-side if needed --}}
      <input type="hidden" id="unit-hidden" name="rows[0][unit]" value="">
    </form>
  </div>

  {{-- Current recipe table --}}
  <div class="card">
    <div class="mb-3">
      <h2 class="text-lg font-semibold">Current Lines</h2>
      <p class="text-sm text-gray-600">Costs use the unit price snapshot stored for each line.</p>
    </div>

    <div class="overflow-x-auto">
      <table class="text-sm">
        <thead>
          <tr>
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
            $mat   = $line->material;
            $unit  = $mat->unit ?? '';
            $snap  = (float)($line->unit_price_snapshot ?? 0);
            $qty   = (float)($line->qty ?? 0);
            $total = $qty * $snap;
            $grand += $total;
          @endphp
          <tr>
            <td class="p-2">{{ $mat->material_name ?? '—' }}</td>
            <td class="p-2 text-right tabular-nums">{{ number_format($qty, 3) }}</td>
            <td class="p-2">{{ $unit }}</td>
            <td class="p-2 text-right tabular-nums">₱ {{ number_format($snap, 2) }}</td>
            <td class="p-2 text-right tabular-nums">₱ {{ number_format($total, 2) }}</td>
            <td class="p-2 text-right">
              <form method="POST"
                    action="{{ route('products.materials.destroy', [$product, $line]) }}"
                    onsubmit="return confirm('Remove this line?')">
                @csrf @method('DELETE')
                <button class="btn btn-primary" style="background:var(--red)">Delete</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="p-4 text-gray-600">No lines yet. Add one above.</td>
          </tr>
        @endforelse
        </tbody>
        <tfoot>
          <tr>
            <th colspan="4" class="p-2 text-right text-gray-700">Grand Total</th>
            <th class="p-2 text-right font-semibold tabular-nums">₱ {{ number_format($grand, 2) }}</th>
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
  // Auto-fill unit price + carry unit through hidden input for the post
  const sel   = document.getElementById('mat-select');
  const price = document.getElementById('price-input');
  const unitH = document.getElementById('unit-hidden');

  sel?.addEventListener('change', () => {
    const opt = sel.options[sel.selectedIndex];
    if (!opt) return;
    price.value = opt.dataset.price || 0;
    unitH.value = opt.dataset.unit  || '';
  });
</script>
@endsection
