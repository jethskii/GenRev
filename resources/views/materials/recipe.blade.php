{{-- resources/views/products/recipe.blade.php (updated + connected) --}}
@extends('layout.mainlayout')

@section('content')
@php
  // Normalized materials catalog for JS (avoid missing keys like default_unit_price)
  $materialsCatalog = $materials->map(function($m){
    return [
      'id' => (int) $m->id,
      'material_name' => (string) $m->material_name,
      'unit' => (string) ($m->unit ?? 'kg'),
      'unit_price' => (float) ($m->unit_price ?? 0),
      'quantity_kg' => (float) ($m->quantity_kg ?? 0),
    ];
  })->values();

  // Normalize recipe lines (support both old + new column names)
  $recipeLines = $recipe->map(function($line){
    $qtyBase    = (float)($line->quantity_per_unit ?? $line->qty ?? 0);
    $wastagePct = (float)($line->wastage_pct ?? 0);
    $price      = (float)($line->unit_price_snapshot ?? 0);

    return [
      'id' => (int) ($line->id ?? 0),
      'material_id' => (int) ($line->material_id ?? $line->ingredient_id ?? 0),
      'quantity_per_unit' => $qtyBase,
      'wastage_pct' => $wastagePct,
      'unit_price_snapshot' => $price,
      'unit' => (string) ($line->unit ?? optional($line->material)->unit ?? 'kg'),
    ];
  })->values();

  $unitLabel = $product->unit ?? 'kg';
@endphp

<div class="pixel-wrapper">
  <div class="pixel-panel">

    {{-- Header + Actions --}}
    <div class="flex flex-col lg:flex-row justify-between items-center gap-4 mb-5">
      <div>
        <div class="pixel-tag mb-2">
          <span class="pixel-tag-dot"></span>
          <span class="pixel-tag-text">Recipe Editor • BOM</span>
        </div>

        <h2 class="pixel-title">
          Recipe for:
          <span class="pixel-title-accent">{{ $product->product_name }}</span>
        </h2>

        <div class="pixel-breadcrumb">
          <a href="{{ route('products.index') }}" class="pixel-link">Products</a>
          <span>/</span>
          <a href="{{ route('products.show', $product) }}" class="pixel-link">{{ $product->product_name }}</a>
          <span>/</span>
          <span>Recipe</span>
        </div>
        <p class="pixel-caption mt-1">Per 1 {{ $unitLabel }} of finished goods</p>
      </div>

      <div class="flex gap-2">
        @if(Route::has('products.materials.defaults'))
          <button id="btnLoadDefaults" type="button" class="pixel-btn pixel-btn-secondary">
            <span class="pixel-btn-label">Load Defaults</span>
          </button>
        @endif

        <button id="btnAddRow" type="button" class="pixel-btn pixel-btn-primary">
          <span class="pixel-btn-label">+ Add Row</span>
        </button>
      </div>
    </div>

    @if(session('success'))
      <div class="pixel-alert pixel-alert-success mb-3">
        {{ session('success') }}
      </div>
    @endif

    @if($errors->any())
      <div class="pixel-alert mb-3" style="background:#fee2e2;">
        <ul class="list-disc pl-4 space-y-0.5">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- Main window frame --}}
    <div class="pixel-window mb-4">
      <div class="pixel-window-bar">
        <span class="pixel-window-title">Material Lines</span>
        <div class="pixel-window-dots">
          <span class="dot red"></span>
          <span class="dot yellow"></span>
          <span class="dot green"></span>
        </div>
      </div>

      {{-- Recipe form --}}
      <form id="recipeForm" action="{{ route('products.recipe.store', $product) }}" method="POST">
        @csrf

        <div class="overflow-x-auto">
          <table class="pixel-table w-full text-left" id="recipeTable">
            <thead>
              <tr>
                <th class="w-12">#</th>
                <th>Material</th>
                <th class="w-24">Unit</th>
                <th class="w-32">Qty / 1 {{ $unitLabel }}</th>
                <th class="w-28 text-center">Wastage %</th>
                <th class="w-40">Unit Price</th>
                <th class="w-40 text-right">Line Total</th>
                <th class="w-20 text-center">Actions</th>
              </tr>
            </thead>

            <tbody id="recipeBody">
              @foreach($recipeLines as $line)
                @php
                  $qtyBase      = (float)$line['quantity_per_unit'];
                  $wastagePct   = (float)$line['wastage_pct'];
                  $unitPrice    = (float)$line['unit_price_snapshot'];
                  $effectiveQty = $qtyBase * (1 + ($wastagePct / 100));
                  $lineTotal    = $effectiveQty * $unitPrice;

                  $lineModel = $recipe->firstWhere('id', $line['id']); // used for delete route below (if exists)
                @endphp

                <tr class="recipe-row" data-line-id="{{ $line['id'] }}">
                  <td class="row-number text-center"></td>

                  <td>
                    <select class="pixel-input w-full ingredient-select" required>
                      @foreach($materials as $m)
                        <option
                          value="{{ $m->id }}"
                          data-unit="{{ $m->unit }}"
                          data-price="{{ (float)($m->unit_price ?? 0) }}"
                          @selected((int)$m->id === (int)$line['material_id'])
                        >{{ $m->material_name }}</option>
                      @endforeach
                    </select>

                    {{-- ✅ CONNECTED TO CONTROLLER VALIDATION (store) --}}
                    <input type="hidden" name="rows[][material_id]" value="{{ $line['material_id'] }}">
                  </td>

                  <td class="unit-cell text-center">{{ $line['unit'] }}</td>

                  <td>
                    <input type="number" step="0.001" min="0"
                      name="rows[][quantity_per_unit]"
                      value="{{ $qtyBase }}"
                      class="pixel-input w-full text-right qty-input">
                  </td>

                  <td>
                    <input type="number" step="0.01" min="0"
                      name="rows[][wastage_pct]"
                      value="{{ $wastagePct }}"
                      class="pixel-input w-full text-right wastage-input">
                  </td>

                  <td>
                    <input type="number" step="0.01" min="0"
                      name="rows[][unit_price_snapshot]"
                      value="{{ $unitPrice }}"
                      class="pixel-input w-full text-right price-input">
                  </td>

                  <td class="text-right line-total">{{ number_format($lineTotal, 2) }}</td>

                  <td class="text-center">
                    @if($lineModel)
                      <form method="POST" action="{{ route('products.recipe.destroy', [$product, $lineModel]) }}"
                            onsubmit="return confirm('Remove this material from recipe?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="pixel-link pixel-link-danger">Delete</button>
                      </form>
                    @else
                      <button type="button" class="pixel-link pixel-link-danger btnRemoveRow">Delete</button>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>

            <tfoot>
              @php
                $grand = (float)$recipe->sum(function($l){
                  $qtyBase    = (float)($l->quantity_per_unit ?? $l->qty ?? 0);
                  $wastagePct = (float)($l->wastage_pct ?? 0);
                  $unitPrice  = (float)($l->unit_price_snapshot ?? 0);
                  $effective  = $qtyBase * (1 + ($wastagePct / 100));
                  return $effective * $unitPrice;
                });
              @endphp
              <tr>
                <td colspan="6" class="text-right">Total Unit Material Cost</td>
                <td class="text-right" id="grandTotal">{{ number_format($grand, 2) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="flex justify-end mt-4">
          <button type="submit" class="pixel-btn pixel-btn-success">
            <span class="pixel-btn-label">Save Recipe</span>
          </button>
        </div>
      </form>
    </div>

    <p class="pixel-footnote">
      Tip: keep ingredient order consistent with your production sheet so operators can follow easily.
    </p>
  </div>
</div>
@endsection

@section('styles')
<style>
  .pixel-wrapper{
    padding:1.5rem;
    background:
      repeating-linear-gradient(to right,#dbeafe 0,#dbeafe 1px,transparent 1px,transparent 24px),
      repeating-linear-gradient(to bottom,#dbeafe 0,#dbeafe 1px,transparent 1px,transparent 24px),
      #fefce8;
  }
  .pixel-panel{
    max-width:1100px;
    margin:0 auto;
    background:#fefcf5;
    border:3px solid #222;
    box-shadow:4px 4px 0 #000;
    padding:1.25rem 1.5rem;
    font-family:"Press Start 2P","VT323",system-ui,monospace;
    font-size:11px;
    color:#111827;
  }
  .pixel-title{ font-size:15px; line-height:1.4; text-transform:uppercase; }
  .pixel-title-accent{
    padding:2px 4px;
    background:#fde68a;
    border:2px solid #111;
    box-shadow:2px 2px 0 #111;
  }
  .pixel-breadcrumb{ margin-top:4px; display:flex; flex-wrap:wrap; gap:4px; color:#6b7280; }
  .pixel-caption{ color:#9ca3af; }
  .pixel-link{ color:#2563eb; text-decoration:none; border-bottom:1px dashed transparent; }
  .pixel-link:hover{ border-bottom-color:#2563eb; }
  .pixel-link-danger{ color:#ef4444; }
  .pixel-footnote{ margin-top:.35rem; color:#9ca3af; font-size:9px; }
  .pixel-tag{
    display:inline-flex; align-items:center; gap:6px;
    padding:3px 8px; border:2px solid #111827; background:#e0f2fe;
    box-shadow:2px 2px 0 #111827;
  }
  .pixel-tag-dot{ width:8px; height:8px; background:#22c55e; border:2px solid #111827; }
  .pixel-tag-text{ letter-spacing:1px; }

  .pixel-btn{
    padding:7px 14px; border:3px solid #111827; border-radius:0;
    box-shadow:3px 3px 0 #111827; background:#e5e7eb; cursor:pointer;
    text-transform:uppercase; font-size:10px;
  }
  .pixel-btn-label{ position:relative; top:1px; }
  .pixel-btn:active{ box-shadow:1px 1px 0 #111827; transform:translate(2px,2px); }
  .pixel-btn-primary{ background:#bfdbfe; }
  .pixel-btn-secondary{ background:#fee2e2; }
  .pixel-btn-success{ background:#bbf7d0; }

  .pixel-alert{ padding:6px 10px; border:3px solid #111827; box-shadow:3px 3px 0 #111827; background:#e5e7eb; }
  .pixel-alert-success{ background:#dcfce7; }

  .pixel-window{ border:3px solid #111827; box-shadow:4px 4px 0 #111827; background:#f9fafb; }
  .pixel-window-bar{
    display:flex; justify-content:space-between; align-items:center;
    padding:4px 8px; background:#0f172a; color:#e5e7eb; border-bottom:3px solid #111827;
  }
  .pixel-window-title{ text-transform:uppercase; letter-spacing:1px; }
  .pixel-window-dots{ display:flex; gap:3px; }
  .pixel-window-dots .dot{ width:8px; height:8px; border:2px solid #111827; background:#f9fafb; }
  .pixel-window-dots .dot.red{ background:#fca5a5; }
  .pixel-window-dots .dot.yellow{ background:#facc15; }
  .pixel-window-dots .dot.green{ background:#4ade80; }

  .pixel-table{ border-collapse:separate; border-spacing:0; width:100%; border-top:3px solid #111827; }
  .pixel-table thead th{
    padding:8px; background:#e0f2fe; border-bottom:3px solid #111827;
    border-right:2px solid #111827; text-transform:uppercase;
  }
  .pixel-table thead th:last-child{ border-right:0; }
  .pixel-table tbody td{ padding:6px 8px; border-bottom:2px solid #d1d5db; border-right:2px solid #e5e7eb; }
  .pixel-table tbody tr:nth-child(odd){ background:#fefce8; }
  .pixel-table tbody tr:nth-child(even){ background:#fff7ed; }
  .pixel-table tfoot td{ padding:8px; border-top:3px solid #111827; background:#f3e8ff; }

  .pixel-input{
    border:2px solid #111827; background:#f9fafb; padding:4px 6px; border-radius:0;
    font-family:inherit; font-size:11px;
  }
  .pixel-input:focus{ outline:none; border-color:#2563eb; background:#e0f2fe; }

  @media (max-width:768px){
    .pixel-panel{ padding:1rem; box-shadow:3px 3px 0 #111827; }
  }
</style>
@endsection

@section('scripts')
<script>
  // ✅ Connected catalog from backend (always consistent)
  const materialsCatalog = @json($materialsCatalog);
  const body  = document.getElementById('recipeBody');
  const grand = document.getElementById('grandTotal');

  function money(n){ return (Math.round(n * 100) / 100).toFixed(2); }

  function renumberRows(){
    document.querySelectorAll('#recipeBody tr').forEach((tr,i)=>{
      const cell = tr.querySelector('.row-number');
      if (cell) cell.textContent = i + 1;
    });
  }

  function recalcRow(tr){
    const qty   = parseFloat(tr.querySelector('.qty-input')?.value || 0) || 0;
    const wast  = parseFloat(tr.querySelector('.wastage-input')?.value || 0) || 0;
    const price = parseFloat(tr.querySelector('.price-input')?.value || 0) || 0;

    const effQty = qty * (1 + (wast / 100));
    const total  = effQty * price;

    const cell  = tr.querySelector('.line-total');
    if (cell) cell.textContent = money(total);
  }

  function recalcAll(){
    let total = 0;
    document.querySelectorAll('#recipeBody tr').forEach(tr => {
      recalcRow(tr);
      const cell = tr.querySelector('.line-total');
      const val  = parseFloat((cell?.textContent || '0').replace(/,/g,'')) || 0;
      total += val;
    });
    if (grand) grand.textContent = money(total);
    renumberRows();
  }

  function makeMaterialSelect(selectedId=null){
    const select = document.createElement('select');
    select.className = 'pixel-input w-full ingredient-select';
    select.required  = true;

    materialsCatalog.forEach(m => {
      const opt = document.createElement('option');
      opt.value = m.id;
      opt.textContent = m.material_name;
      opt.dataset.unit  = m.unit || 'kg';
      opt.dataset.price = (m.unit_price ?? 0);
      if (selectedId && Number(selectedId) === Number(m.id)) opt.selected = true;
      select.appendChild(opt);
    });

    return select;
  }

  function addRow({ material_id=null, qty=0, wastage_pct=0, unit_price=0 } = {}){
    const tr = document.createElement('tr');
    tr.className = 'recipe-row';

    tr.innerHTML = `
      <td class="row-number text-center"></td>
      <td></td>
      <td class="unit-cell text-center"></td>
      <td>
        <input type="number" step="0.001" min="0"
          name="rows[][quantity_per_unit]"
          value="${qty}"
          class="pixel-input w-full text-right qty-input">
      </td>
      <td>
        <input type="number" step="0.01" min="0"
          name="rows[][wastage_pct]"
          value="${wastage_pct}"
          class="pixel-input w-full text-right wastage-input">
      </td>
      <td>
        <input type="number" step="0.01" min="0"
          name="rows[][unit_price_snapshot]"
          value="${unit_price}"
          class="pixel-input w-full text-right price-input">
      </td>
      <td class="text-right line-total">0.00</td>
      <td class="text-center">
        <button type="button" class="pixel-link pixel-link-danger btnRemoveRow">Delete</button>
      </td>
    `;

    const materialCell = tr.children[1];
    const select = makeMaterialSelect(material_id);
    const hidden = document.createElement('input');
    hidden.type  = 'hidden';

    // ✅ CONNECTED (your ProductRecipe + controller use material_id)
    hidden.name  = 'rows[][material_id]';
    hidden.value = material_id || (select.value ?? '');

    materialCell.appendChild(select);
    materialCell.appendChild(hidden);

    function syncFromSelect(){
      const opt = select.options[select.selectedIndex];
      const unit = opt?.dataset?.unit || 'kg';
      const defPrice = parseFloat(opt?.dataset?.price || 0) || 0;

      tr.querySelector('.unit-cell').textContent = unit;

      const priceInput = tr.querySelector('.price-input');
      if (!priceInput.value || Number(priceInput.value) === 0) {
        priceInput.value = defPrice;
      }

      hidden.value = select.value;
      recalcAll();
    }

    select.addEventListener('change', syncFromSelect);

    tr.querySelector('.qty-input').addEventListener('input', recalcAll);
    tr.querySelector('.wastage-input').addEventListener('input', recalcAll);
    tr.querySelector('.price-input').addEventListener('input', recalcAll);
    tr.querySelector('.btnRemoveRow').addEventListener('click', () => {
      tr.remove();
      recalcAll();
    });

    body.appendChild(tr);
    syncFromSelect();

    // if unit_price was passed in and not zero, keep it
    if (Number(unit_price) > 0) {
      tr.querySelector('.price-input').value = unit_price;
      recalcAll();
    }
  }

  // Wire existing rows
  document.querySelectorAll('#recipeBody tr').forEach(tr => {
    const select = tr.querySelector('.ingredient-select');
    const hidden = tr.querySelector('input[type="hidden"][name="rows[][material_id]"]');

    const sync = () => {
      const opt = select.options[select.selectedIndex];
      tr.querySelector('.unit-cell').textContent = opt.dataset.unit || 'kg';

      const priceInput = tr.querySelector('.price-input');
      if (Number(priceInput.value) === 0) priceInput.value = opt.dataset.price || 0;

      if (hidden) hidden.value = select.value;
      recalcAll();
    };

    select?.addEventListener('change', sync);
    tr.querySelector('.qty-input')?.addEventListener('input', recalcAll);
    tr.querySelector('.wastage-input')?.addEventListener('input', recalcAll);
    tr.querySelector('.price-input')?.addEventListener('input', recalcAll);

    // Ensure hidden matches select on load
    if (hidden && select) hidden.value = select.value;
  });

  document.getElementById('btnAddRow')?.addEventListener('click', () => addRow());

  // Load defaults (if route exists)
  document.getElementById('btnLoadDefaults')?.addEventListener('click', async () => {
    body.innerHTML = '';
    const res = await fetch(`{{ Route::has('products.materials.defaults') ? route('products.materials.defaults', $product) : '' }}`, {
      headers: { 'X-Requested-With':'XMLHttpRequest' }
    });

    if (!res.ok) { alert('Failed to load defaults.'); return; }

    const rows = await res.json();
    rows.forEach(r => addRow({
      material_id: r.material_id ?? r.ingredient_id,
      qty: r.quantity_per_unit ?? r.qty ?? 0,
      wastage_pct: r.wastage_pct ?? 0,
      unit_price: r.unit_price_snapshot ?? r.unit_price ?? r.unit_price_snapshot ?? 0
    }));

    recalcAll();
  });

  // Initial totals
  recalcAll();
</script>
@endsection
