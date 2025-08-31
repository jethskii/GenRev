@extends('layout.mainlayout')

@section('content')
<div class="bg-dark-bg text-white border border-dark-line p-6 rounded shadow-md">

  {{-- Header + Actions --}}
  <div class="flex flex-col lg:flex-row justify-between items-center gap-4 mb-6">
    <div>
      <h2 class="text-xl font-bold">
        Recipe (BOM) for: <span class="text-armygreen">{{ $product->product_name }}</span>
      </h2>
      <div class="text-sm text-gray-400 mt-1">
        <a href="{{ route('products.index') }}" class="hover:underline">Products</a>
        <span class="mx-1">/</span>
        <a href="{{ route('products.show', $product) }}" class="hover:underline">{{ $product->product_name }}</a>
        <span class="mx-1">/</span>
        <span>Recipe</span>
      </div>
      <p class="text-xs text-gray-400 mt-1">Per 1 {{ $product->unit ?? 'kg' }} of finished goods</p>
    </div>

    <div class="flex gap-2">
      @if(Route::has('products.materials.defaults'))
        <button id="btnLoadDefaults" class="btn-armygreen">Load Defaults</button>
      @endif
      <button id="btnAddRow" class="px-4 py-2 rounded text-sm bg-gray-600 hover:bg-gray-700 transition">+ Add Row</button>
    </div>
  </div>

  @if(session('success'))
    <div class="mb-4 text-green-400 text-sm">{{ session('success') }}</div>
  @endif

  {{-- Recipe form --}}
  <form id="recipeForm" action="{{ route('products.recipe.store', $product) }}" method="POST">
    @csrf
    <div class="overflow-x-auto rounded-lg">
      <table class="w-full text-sm text-left bg-dark-bg rounded-lg overflow-hidden border-collapse" id="recipeTable">
        <thead class="bg-sidebar text-white text-xs uppercase">
          <tr>
            <th class="py-3 px-4 border-b border-dark-line w-12">#</th>
            <th class="py-3 px-4 border-b border-dark-line">Material</th>
            <th class="py-3 px-4 border-b border-dark-line w-24">Unit</th>
            <th class="py-3 px-4 border-b border-dark-line w-32">Qty / 1 {{ $product->unit ?? 'kg' }}</th>
            <th class="py-3 px-4 border-b border-dark-line w-40">Unit Price (snapshot)</th>
            <th class="py-3 px-4 border-b border-dark-line w-40 text-right">Line Total</th>
            <th class="py-3 px-4 border-b border-dark-line w-20 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="text-gray-100 divide-y divide-dark-line" id="recipeBody">
          @foreach($recipe as $line)
            <tr>
              <td class="py-3 px-4 row-number"></td>

              <td class="py-3 px-4">
                <select class="input-dark w-full ingredient-select" required>
                  @foreach($materials as $m)
                    <option value="{{ $m->id }}"
                      data-unit="{{ $m->unit }}"
                      data-price="{{ $m->default_unit_price }}"
                      @selected($m->id === $line->material_id)
                    >{{ $m->material_name }}</option>
                  @endforeach
                </select>
                <input type="hidden" name="rows[][ingredient_id]" value="{{ $line->material_id }}">
              </td>

              <td class="py-3 px-4 unit-cell">{{ $line->material->unit ?? '—' }}</td>

              <td class="py-3 px-4">
                <input type="number" step="0.001" min="0" name="rows[][qty]" value="{{ $line->qty }}" class="input-dark w-28 qty-input">
              </td>

              <td class="py-3 px-4">
                <input type="number" step="0.01" min="0" name="rows[][unit_price]" value="{{ $line->unit_price_snapshot }}" class="input-dark w-32 price-input">
              </td>

              @php
                $lineTotal = (float)($line->qty ?? 0) * (float)($line->unit_price_snapshot ?? 0);
              @endphp
              <td class="py-3 px-4 text-right line-total">{{ number_format($lineTotal, 2) }}</td>

              <td class="py-3 px-4 text-center">
                <form method="POST" action="{{ route('products.recipe.destroy', [$product, $line]) }}"
                      onsubmit="return confirm('Remove this material from recipe?');">
                  @csrf @method('DELETE')
                  <button type="submit" class="text-red-400 hover:underline">Delete</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>

        <tfoot class="bg-sidebar text-white text-xs uppercase">
          @php
            $grand = (float)($recipe->sum(fn($l) => (float)($l->qty ?? 0) * (float)($l->unit_price_snapshot ?? 0)));
          @endphp
          <tr>
            <td colspan="5" class="py-3 px-4 text-right">Total Unit Material Cost</td>
            <td class="py-3 px-4 text-right" id="grandTotal">{{ number_format($grand, 2) }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="flex justify-end mt-4">
      <button type="submit" class="btn-armygreen">Save Recipe</button>
    </div>
  </form>
</div>
@endsection

@section('scripts')
<script>
  // Catalog: id, material_name, unit, default_unit_price
  const materialsCatalog = @json($materials);
  const body  = document.getElementById('recipeBody');
  const grand = document.getElementById('grandTotal');

  function money(n){ return (Math.round(n * 100)/100).toFixed(2); }
  function renumberRows(){
    document.querySelectorAll('#recipeBody tr').forEach((tr,i)=> tr.querySelector('.row-number').textContent = i+1);
  }
  function recalcRow(tr){
    const qty   = parseFloat(tr.querySelector('.qty-input')?.value || 0);
    const price = parseFloat(tr.querySelector('.price-input')?.value || 0);
    tr.querySelector('.line-total').textContent = money(qty * price);
  }
  function recalcAll(){
    let total = 0;
    document.querySelectorAll('#recipeBody tr').forEach(tr => {
      recalcRow(tr);
      total += parseFloat(tr.querySelector('.line-total').textContent || 0);
    });
    grand.textContent = money(total);
    renumberRows();
  }

  function makeMaterialSelect(selectedId=null){
    const select = document.createElement('select');
    select.className = 'input-dark w-full ingredient-select';
    select.required  = true;
    materialsCatalog.forEach(m => {
      const opt = document.createElement('option');
      opt.value = m.id;
      opt.textContent = m.material_name;
      opt.dataset.unit  = m.unit;
      opt.dataset.price = m.default_unit_price;
      if (selectedId && Number(selectedId) === Number(m.id)) opt.selected = true;
      select.appendChild(opt);
    });
    return select;
  }

  function addRow({material_id=null, unit='', qty=0, unit_price=0} = {}){
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="py-3 px-4 row-number"></td>
      <td class="py-3 px-4"></td>
      <td class="py-3 px-4 unit-cell">${unit || ''}</td>
      <td class="py-3 px-4"><input type="number" step="0.001" min="0" name="rows[][qty]" value="${qty}" class="input-dark w-28 qty-input"></td>
      <td class="py-3 px-4"><input type="number" step="0.01" min="0" name="rows[][unit_price]" value="${unit_price}" class="input-dark w-32 price-input"></td>
      <td class="py-3 px-4 text-right line-total">0.00</td>
      <td class="py-3 px-4 text-center"><button type="button" class="text-red-400 hover:underline btnRemoveRow">Delete</button></td>
    `;

    const cell   = tr.children[1];
    const select = makeMaterialSelect(material_id);
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'rows[][ingredient_id]';
    hidden.value = material_id || (select.value ?? '');
    cell.appendChild(select);
    cell.appendChild(hidden);

    select.addEventListener('change', () => {
      const opt = select.options[select.selectedIndex];
      tr.querySelector('.unit-cell').textContent = opt.dataset.unit || '';
      const priceInput = tr.querySelector('.price-input');
      if (!priceInput.value || Number(priceInput.value) === 0) {
        priceInput.value = opt.dataset.price || 0;
      }
      hidden.value = select.value;
      recalcAll();
    });
    tr.querySelector('.qty-input').addEventListener('input', recalcAll);
    tr.querySelector('.price-input').addEventListener('input', recalcAll);
    tr.querySelector('.btnRemoveRow').addEventListener('click', () => { tr.remove(); recalcAll(); });

    body.appendChild(tr);

    const opt = select.options[select.selectedIndex];
    tr.querySelector('.unit-cell').textContent = opt?.dataset?.unit || unit || '';
    if (!unit_price || Number(unit_price) === 0) {
      tr.querySelector('.price-input').value = opt?.dataset?.price || 0;
    }
    hidden.value = select.value;

    recalcAll();
  }

  // wire existing rows
  document.querySelectorAll('#recipeBody tr').forEach(tr => {
    const select = tr.querySelector('.ingredient-select');
    const hidden = tr.querySelector('input[type="hidden"][name="rows[][ingredient_id]"]');
    select?.addEventListener('change', () => {
      const opt = select.options[select.selectedIndex];
      tr.querySelector('.unit-cell').textContent = opt.dataset.unit || '';
      const priceInput = tr.querySelector('.price-input');
      if (Number(priceInput.value) === 0) priceInput.value = opt.dataset.price || 0;
      hidden.value = select.value;
      recalcAll();
    });
    tr.querySelector('.qty-input')?.addEventListener('input', recalcAll);
    tr.querySelector('.price-input')?.addEventListener('input', recalcAll);
    tr.querySelector('.btnRemoveRow')?.addEventListener('click', () => { tr.remove(); recalcAll(); });
  });

  document.getElementById('btnAddRow')?.addEventListener('click', () => addRow());

  document.getElementById('btnLoadDefaults')?.addEventListener('click', async () => {
    body.innerHTML = '';
    const res  = await fetch(`{{ route('products.materials.defaults', $product) }}`);
    const rows = await res.json();
    rows.forEach(r => addRow({
      material_id: r.ingredient_id,
      unit: r.unit,
      qty: r.qty,
      unit_price: r.unit_price
    }));
    recalcAll();
  });

  // initial totals
  recalcAll();
</script>
@endsection
