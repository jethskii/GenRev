@extends('layout.mainlayout')

@section('title', 'Sales')

@section('head')
<link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  /* Jost + Liquid/Glass theme */
  body, p, ul, li, a, button, input, select { font-family: 'Jost', system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
  body {
    background: linear-gradient(135deg, #1F1E1E 0%, #001C00 100%);
    min-height: 100vh; overflow-x: hidden;
  }
  body::before {
    content:''; position:fixed; top:-50%; left:-50%; width:200%; height:200%;
    background: linear-gradient(to bottom right,
      rgba(18,108,7,.15) 0%,
      rgba(113,200,98,.15) 25%,
      rgba(210,220,50,.12) 50%,
      rgba(113,200,98,.15) 75%,
      rgba(10,56,14,.15) 100%
    );
    transform: rotate(30deg); animation: liquidFlow 15s linear infinite;
    z-index:-1; opacity:.5;
  }
  @keyframes liquidFlow {
    0% { transform: rotate(30deg) translate(-10%, -10%); }
    50% { transform: rotate(30deg) translate(10%, 10%); }
    100% { transform: rotate(30deg) translate(-10%, -10%); }
  }

  .liquid-card {
    position:relative; overflow:hidden; border-radius:20px; backdrop-filter:blur(10px);
    background:rgba(31,30,30,.7); border: .5px solid rgba(255,255,255,.2);
    box-shadow:0 8px 32px rgba(0,28,0,.3);
  }
  .liquid-card::before{
    content:''; position:absolute; inset:0; pointer-events:none; z-index:-1;
    background:linear-gradient(45deg, rgba(4,119,5,.10), rgba(237,209,0,.10), rgba(4,119,5,.10));
    animation: cardShine 8s ease infinite;
  }
  @keyframes cardShine { 0%{opacity:.3} 50%{opacity:.1} 100%{opacity:.3} }

  .liquid-table { width:100%; border-collapse:separate; border-spacing:0; overflow:hidden; border-radius:15px; }
  .liquid-table thead { background: linear-gradient(90deg, #047705 0%, #0aad0a 100%); position:sticky; top:0; z-index:5; }
  .liquid-table th { padding:12px 14px; text-align:left; color:#fff; font-weight:600; font-size:.75rem; letter-spacing:.4px; text-transform:uppercase; }
  .liquid-table td { padding:12px 14px; color:#e6f4ea; border-bottom:1px solid rgba(255,255,255,.06); background: rgba(255,255,255,.02); }
  .liquid-table tr:hover td { background: rgba(4,119,5,.08); }

  .toolbar input, .toolbar select {
    background: rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.15);
    color:#fff; border-radius:12px; padding:.55rem .8rem; outline:none;
  }
  .toolbar input:focus, .toolbar select:focus { border-color:#047705; box-shadow:0 0 0 2px rgba(4,119,5,.25); }

  .btn-primary{
    background: linear-gradient(90deg,#047705 0%, #0aad0a 100%);
    color:#fff; border:1px solid rgba(255,255,255,.15);
    border-radius:12px; padding:.55rem 1rem; box-shadow:0 6px 18px rgba(4,119,5,.35);
    transition:.2s;
  }
  .btn-primary:hover{ transform: translateY(-1px); }

  .chip { display:inline-flex; align-items:center; padding:.25rem .55rem; border-radius:999px; font-size:.72rem; border:1px solid transparent; }
  .chip-paid{ background:#04770526; color:#9AF2A8; border-color:#04770555; }
  .chip-pending{ background:#EDD10026; color:#FFE877; border-color:#EDD10066; }
  .chip-completed{ background:#3B82F626; color:#CBE1FE; border-color:#3B82F677; }
  .chip-cancelled{ background:#ef444426; color:#fecaca; border-color:#ef444477; }
  .chip-refunded{ background:#f59e0b26; color:#fde68a; border-color:#f59e0b66; }
  .chip-default{ background:#94a3b826; color:#e5e7eb; border-color:#94a3b866; }

  /* Unit-type chip inside table */
  .u-chip{
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.16rem .45rem; border-radius:999px; font-size:.68rem; font-weight:600;
    border:1px solid rgba(255,255,255,.18); background:rgba(255,255,255,.10); color:#e6f4ea;
    margin-left:.35rem;
  }

  .flash { border-radius:12px; padding:.6rem .9rem; display:flex; justify-content:space-between; align-items:center; }
  .flash-success{ background:#047705; color:#fff; }
  .flash-error{ background:#dc2626; color:#fff; }
  .flash button{ font-size:1.1rem; line-height:1; }
</style>
@endsection

@section('content')
<div class="px-6 py-8">
  {{-- Flash messages --}}
  @if(session('success'))
    <div id="successAlert" class="flash flash-success mb-4">
      <span>{{ session('success') }}</span>
      <button onclick="document.getElementById('successAlert').style.display='none'">&times;</button>
    </div>
  @endif

  @if(session('error'))
    <div id="errorAlert" class="flash flash-error mb-4">
      <span>{{ session('error') }}</span>
      <button onclick="document.getElementById('errorAlert').style.display='none'">&times;</button>
    </div>
  @endif

  @if($errors->any())
    <div class="flash flash-error mb-4" style="display:block;">
      <div>
        @foreach($errors->all() as $e)
          <div>{{ $e }}</div>
        @endforeach
      </div>
      <button onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
  @endif

  <div class="liquid-card p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
      <h1 class="text-2xl font-bold text-white" style="text-shadow:-2px 1px 0px #047705;">Sales</h1>
      <button onclick="openModal()" class="btn-primary">+ Add New Sale</button>
    </div>

    {{-- Tools --}}
    <div class="toolbar flex flex-col sm:flex-row gap-3 mb-5">
      <input type="text" id="searchInput" placeholder="Search product / invoice / status…" class="w-full sm:w-80">
      <select id="statusFilter" class="w-full sm:w-56">
        <option value="">Filter by Status</option>
        <option value="Paid">Paid</option>
        <option value="Pending">Pending</option>
        <option value="Completed">Completed</option>
        <option value="Cancelled">Cancelled</option>
        <option value="Refunded">Refunded</option>
      </select>
    </div>

    {{-- Table --}}
    <div class="overflow-auto rounded-xl border border-white/10">
      <table class="liquid-table">
        <thead>
          <tr>
            <th>Invoice</th>
            <th>Product</th>
            <th>Date</th>
            <th class="text-right">Quantity</th>
            <th class="text-right">Unit Price</th>
            <th class="text-right">Total</th>
            <th>Status</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($sales as $sale)
            @php
              // status chip
              $status = strtolower($sale->status ?? '');
              $chipClass = match($status){
                'paid' => 'chip-paid', 'pending' => 'chip-pending', 'completed' => 'chip-completed',
                'cancelled' => 'chip-cancelled', 'refunded' => 'chip-refunded', default => 'chip-default',
              };

              // robust product name
              $pname = $sale->display_product
                        ?? ($sale->product ?? optional($sale->productRef)->product_name ?? optional($sale->product)->product_name ?? '—');

              // robust date
              $dateVal = $sale->order_date ?? $sale->date ?? $sale->created_at ?? null;
              $dateStr = $dateVal ? \Illuminate\Support\Carbon::parse($dateVal)->format('Y-m-d') : '—';

              // robust quantity (kg or plain)
              $qty = (float)($sale->quantity_kg ?? $sale->quantity ?? 0);

              // robust pricing
              $unit = (float)($sale->unit_price ?? $sale->price ?? 0);
              $total = (float)($sale->total_price ?? $sale->total ?? ($qty * $unit));

              // NEW: unit-type chip (per pack / per bag)
              $uTypeRaw = $sale->unit_type ?? $sale->unit ?? null;
              $uType    = in_array($uTypeRaw, ['pack','bag'], true) ? $uTypeRaw : null;

              // invoice no
              $invoice = $sale->invoice_number ?? $sale->order_number ?? ('INV-' . $sale->id);
            @endphp
            <tr>
              <td class="text-emerald-300 font-semibold cursor-pointer" onclick="openInvoiceModal({{ $sale->id }})">
                {{ $invoice }}
              </td>
              <td>{{ $pname }}</td>
              <td>{{ $dateStr }}</td>
              <td class="text-right">{{ is_numeric($qty) ? (strpos(number_format($qty,3),'000') !== false ? (int)$qty : number_format($qty,3)) : '0' }}</td>
              <td class="text-right">
                ₱{{ number_format($unit, 2) }}
                @if($uType)
                  <span class="u-chip">per {{ $uType }}</span>
                @endif
              </td>
              <td class="text-right">₱{{ number_format($total, 2) }}</td>
              <td>
                <span class="chip {{ $chipClass }}">{{ ucfirst($status ?: 'Unknown') }}</span>
              </td>
              <td>
                <div class="flex flex-wrap justify-center items-center gap-3">
                  {{-- Edit --}}
                  <button onclick="handleEdit({{ $sale->id }})" title="Edit" class="text-indigo-300 hover:text-indigo-100">Edit</button>

                  {{-- Receipt --}}
                  <a href="{{ route('sales.receipt', $sale) }}" target="_blank" title="Receipt" class="text-emerald-300 hover:text-emerald-100">Receipt</a>

                  {{-- PDF --}}
                  <a href="{{ route('sales.download', $sale) }}" title="Download PDF" class="text-yellow-300 hover:text-yellow-100">PDF</a>

                  {{-- Refund (optional route) --}}
                  <form action="{{ route('sales.refund', $sale->id) }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to refund this sale?');" class="inline">
                    @csrf
                    <button type="submit" title="Refund" class="text-amber-300 hover:text-amber-100">Refund</button>
                  </form>

                  {{-- Delete --}}
                  <form action="{{ route('sales.destroy', $sale) }}" method="POST"
                        onsubmit="return confirm('Delete this sale?');" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" title="Delete" class="text-rose-300 hover:text-rose-100">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-white/70 py-6">No sales records found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Footer meta (optional) --}}
    <div class="flex items-center justify-between text-sm text-white/60 mt-4">
      <div>
        <span class="mr-2">INV-</span>{{ now()->format('Ymd') }}<span class="mx-2">—</span>{{ $nextInvoice ?? '' }}
      </div>
      <div></div>
    </div>
  </div>
</div>

{{-- Your modals partial (ensure IDs used below exist) --}}
@include('modals.modals')
@endsection

@push('scripts')
<script>
  // Modal handles (IDs must exist in your partial)
  const addModal  = document.getElementById('addSaleModal');
  const editModal = document.getElementById('editSaleModal');

  function openModal() {
    const inv      = @json($nextInvoice ?? null);
    const invField = document.getElementById('invoice_number');
    if (inv && invField) invField.value = inv;

    addModal?.classList.remove('hidden');
    addModal?.classList.add('flex');
  }
  function closeModal() {
    addModal?.classList.remove('flex');
    addModal?.classList.add('hidden');
  }
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

  // Prefer global openEditModal(id) (from your modal partial). Fallback to API.
  async function handleEdit(id){
    if (typeof window.openEditModal === 'function') {
      window.openEditModal(id);
      return;
    }
    try {
      const res = await fetch(`/api/sales/${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) throw new Error('Failed to load sale');
      const data = await res.json();

      // Minimal fallback population (IDs must match your edit modal inputs)
      document.getElementById('edit-sale-id')?.setAttribute('value', data.id);
      const prodSel = document.getElementById('edit-product-id');
      if (prodSel && data.product_id) prodSel.value = String(data.product_id);

      const d = data.order_date || data.date || null;
      const iso = d ? new Date(d).toISOString().slice(0,10) : '';
      document.getElementById('edit-date')?.setAttribute('value', iso);

      const qty = (data.quantity_kg ?? data.quantity ?? 0);
      document.getElementById('edit-quantity')?.setAttribute('value', qty);

      const price = (data.unit_price ?? data.price ?? '');
      document.getElementById('edit-price')?.setAttribute('value', price);

      const status = (data.status ?? 'Completed');
      const statusSel = document.getElementById('edit-status');
      if (statusSel) statusSel.value = status;

      const form = document.getElementById('editSaleForm');
      if (form) form.action = `/sales/${data.id}`;

      editModal?.classList.remove('hidden');
      editModal?.classList.add('flex');
    } catch (e) {
      alert('Unable to open edit modal.');
    }
  }

  function closeEditModal() {
    editModal?.classList.add('hidden');
    editModal?.classList.remove('flex');
  }

  function openInvoiceModal(id) {
    window.open(`/sales/${id}/receipt`, '_blank');
  }

  // Search + Status filter
  (function(){
    const searchInput  = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const rows         = Array.from(document.querySelectorAll('tbody tr'));

    function applyFilters() {
      const term = (searchInput.value || '').toLowerCase();
      const status = statusFilter.value;

      rows.forEach(tr => {
        const tds = tr.querySelectorAll('td');
        if (!tds.length) return;

        const invoice = (tds[0].textContent || '').toLowerCase();
        const product = (tds[1].textContent || '').toLowerCase();
        const rowStatus = (tds[6].textContent || '').trim();

        const matchTerm = !term || invoice.includes(term) || product.includes(term) || rowStatus.toLowerCase().includes(term);
        const matchStatus = !status || rowStatus === status;

        tr.style.display = (matchTerm && matchStatus) ? '' : 'none';
      });
    }

    searchInput?.addEventListener('input', applyFilters);
    statusFilter?.addEventListener('change', applyFilters);
  })();

  // Optional: Available stock helper in Add Sale modal
  document.addEventListener('DOMContentLoaded', () => {
    const productSel = document.getElementById('product_id');
    const qtyInput   = document.getElementById('quantity');
    const availHelp  = document.getElementById('availableHelp');
    let available    = 0;

    productSel?.addEventListener('change', async () => {
      if (!productSel.value) { if (availHelp) availHelp.textContent = 'Available: —'; return; }
      try {
        const res  = await fetch(@json(route('sales.available')) + '?product_id=' + encodeURIComponent(productSel.value), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        available  = data.available ?? 0;
        if (availHelp) availHelp.textContent = 'Available: ' + available;
      } catch {
        available = 0;
        if (availHelp) availHelp.textContent = 'Available: —';
      }
    });

    qtyInput?.addEventListener('input', () => {
      const val = parseFloat(qtyInput.value || '0');
      if (available && val > available) {
        qtyInput.setCustomValidity('Quantity exceeds available stock (' + available + ').');
      } else {
        qtyInput.setCustomValidity('');
      }
    });
  });
</script>
@endpush
