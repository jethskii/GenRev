@extends('layout.mainlayout')

@section('content')
<div class="px-6 py-6 text-gray-900" aria-label="Sales Archive">
  {{-- HEADER --}}
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
    <div>
      <h1 class="text-2xl font-bold">Sales Archive</h1>
      <p class="text-sm text-gray-500">Soft-deleted sales. Restore them or delete permanently.</p>
    </div>
    <a href="{{ route('sales') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-gray-50">
      &larr; Back to Sales
    </a>
  </div>

  {{-- INLINE ALERTS (top of page) --}}
  @if (session('success'))
    <div class="mb-3 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm">
      {{ session('success') }}
    </div>
  @endif

  @if (session('error'))
    <div class="mb-3 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
      {{ session('error') }}
    </div>
  @endif

  @if (session('info'))
    <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 text-sm">
      {{ session('info') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="mb-3 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
      <ul class="list-disc list-inside space-y-0.5">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Bulk actions --}}
  <form id="bulkForm" method="POST" class="light-card p-4 mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    @csrf
    <div class="text-sm text-gray-600">
      <span class="font-semibold">{{ $sales->total() }}</span> archived sale{{ $sales->total() === 1 ? '' : 's' }}
    </div>
    <div class="flex items-center gap-2">
      <button type="submit" formaction="{{ route('sales.restoreMany') }}" id="bulkRestoreBtn" class="rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 px-3 py-1.5 text-sm font-semibold hover:bg-emerald-100 disabled:opacity-40 disabled:cursor-not-allowed" disabled>
        Restore Selected
      </button>
      <button type="submit" formaction="{{ route('sales.forceDeleteMany') }}" id="bulkDeleteBtn" class="rounded-lg bg-rose-50 text-rose-800 border border-rose-200 px-3 py-1.5 text-sm font-semibold hover:bg-rose-100 disabled:opacity-40 disabled:cursor-not-allowed" disabled onclick="return confirm('Permanently delete the selected sale(s)? This cannot be undone.');">
        Delete Selected Forever
      </button>
    </div>
  </form>

  {{-- Table --}}
  <div class="light-card overflow-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs font-bold uppercase tracking-wide text-gray-500 bg-gray-50 border-b border-gray-200">
          <th class="px-4 py-3"><input type="checkbox" id="checkAll" class="h-4 w-4"></th>
          <th class="px-4 py-3">Product</th>
          <th class="px-4 py-3">Batch</th>
          <th class="px-4 py-3 text-right">Qty (kg)</th>
          <th class="px-4 py-3 text-right">Total</th>
          <th class="px-4 py-3">Sale Date</th>
          <th class="px-4 py-3">Deleted</th>
          <th class="px-4 py-3 text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($sales as $sale)
          <tr class="border-b border-gray-100 hover:bg-gray-50">
            <td class="px-4 py-3 align-top">
              <input type="checkbox" form="bulkForm" name="ids[]" value="{{ $sale->id }}" class="rowCheck h-4 w-4">
            </td>
            <td class="px-4 py-3 align-top">
              <div class="font-medium">{{ $sale->productRef?->product_name ?? $sale->display_product ?? '—' }}</div>
              <div class="text-xs text-gray-500">#{{ $sale->id }}</div>
            </td>
            <td class="px-4 py-3 align-top">
              {{ $sale->production?->batch_number ?? '—' }}
            </td>
            <td class="px-4 py-3 align-top text-right">
              {{ number_format($sale->qtyKg(), 2) }}
            </td>
            <td class="px-4 py-3 align-top text-right">
              &#8369; {{ number_format($sale->totalValue(), 2) }}
            </td>
            <td class="px-4 py-3 align-top">
              {{ optional($sale->sale_date)->format('Y-m-d') ?? '—' }}
            </td>
            <td class="px-4 py-3 align-top">
              {{ $sale->deleted_at ? \Carbon\Carbon::parse($sale->deleted_at)->format('Y-m-d H:i') : '—' }}
            </td>
            <td class="px-4 py-3 align-top">
              <div class="flex items-center justify-end gap-2">
                <form method="POST" action="{{ route('sales.restore', $sale->id) }}">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 px-3 py-1 text-xs font-semibold hover:bg-emerald-100">
                    Restore
                  </button>
                </form>
                <form method="POST" action="{{ route('sales.forceDelete', $sale->id) }}" onsubmit="return confirm('Permanently delete this sale? This cannot be undone.');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="rounded-lg bg-rose-50 text-rose-800 border border-rose-200 px-3 py-1 text-xs font-semibold hover:bg-rose-100">
                    Delete Forever
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="px-4 py-10 text-center text-gray-500">No archived sales found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="flex justify-end mt-4">
    {{ $sales->withQueryString()->links() }}
  </div>
</div>
@endsection

@section('scripts')
  <script>
    (function () {
      const checkAll = document.getElementById('checkAll');
      const rowChecks = () => Array.from(document.querySelectorAll('.rowCheck'));
      const bulkRestoreBtn = document.getElementById('bulkRestoreBtn');
      const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

      function syncBulkState() {
        const any = rowChecks().some(cb => cb.checked);
        if (bulkRestoreBtn) bulkRestoreBtn.disabled = !any;
        if (bulkDeleteBtn) bulkDeleteBtn.disabled = !any;
      }

      checkAll?.addEventListener('change', function () {
        rowChecks().forEach(cb => cb.checked = checkAll.checked);
        syncBulkState();
      });

      document.addEventListener('change', function (e) {
        if (e.target.classList && e.target.classList.contains('rowCheck')) {
          syncBulkState();
        }
      });

      syncBulkState();
    })();
  </script>
@endsection
