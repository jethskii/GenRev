{{-- resources/views/production/archived.blade.php --}}
@extends('layout.mainlayout')

@section('title', 'Archived • Production')
@section('page_title', 'Archived Production')

@section('styles')
  <style>
    :root {
      --bg: #f7f8fb;
      --ink: #0f172a;
      --muted: #64748b;
      --line: #e5e7eb;
      --card: #ffffff;
      --shadow: 0 8px 28px rgba(15, 23, 42, .08);
      --emerald: #10b981;
      --blue: #2563eb;
      --red: #ef4444;
      --amber: #f59e0b;
      --indigo: #6366f1;
      --bg-card: #fff;
    }

    body {
      background: var(--bg);
      color: var(--ink);
    }

    /* Buttons (local light theme) */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      padding: .55rem .85rem;
      border-radius: .75rem;
      font-weight: 600;
      border: 1px solid transparent;
      transition: .18s ease;
      box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
    }

    .btn svg {
      width: 1rem;
      height: 1rem
    }

    .btn:disabled {
      opacity: .45;
      cursor: not-allowed
    }

    .btn-primary {
      background: var(--red);
      color: #fff;
    }

    .btn-primary:hover {
      filter: brightness(.95)
    }

    .btn-secondary-green {
      background: #ecfdf5;
      color: #065f46;
      border-color: #a7f3d0;
    }

    .btn-secondary-green:hover {
      background: #d1fae5
    }

    .btn-secondary-blue {
      background: #eff6ff;
      color: #1e40af;
      border-color: #bfdbfe;
    }

    .btn-secondary-blue:hover {
      background: #dbeafe
    }

    .btn-ghost {
      background: #fff;
      color: var(--ink);
      border-color: var(--line);
    }

    .btn-ghost:hover {
      background: #f9fafb
    }

    /* Pills */
    .pill {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      padding: .25rem .6rem;
      border-radius: 9999px;
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .02em;
      border: 1px solid;
    }

    .pill-gray {
      background: #f1f5f9;
      color: #334155;
      border-color: #e2e8f0;
    }

    .pill-green {
      background: #ecfdf5;
      color: #065f46;
      border-color: #a7f3d0;
    }

    .pill-red {
      background: #fef2f2;
      color: #7f1d1d;
      border-color: #fecaca;
    }

    .pill-amber {
      background: #fffbeb;
      color: #78350f;
      border-color: #fde68a;
    }

    .pill-blue {
      background: #eff6ff;
      color: #1e40af;
      border-color: #bfdbfe;
    }

    /* Cards + table */
    .shine {
      background: linear-gradient(180deg, rgba(255, 255, 255, 1) 0%, rgba(250, 250, 255, 1) 100%);
      border: 1px solid var(--line);
    }

    .soft-card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 1rem;
      box-shadow: var(--shadow);
    }

    .kbd {
      background: #f3f4f6;
      border: 1px solid #e5e7eb;
      border-bottom-width: 2px;
      border-radius: .5rem;
      padding: .1rem .4rem;
      font-size: .75rem;
    }

    .tbl {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }

    .tbl thead th {
      font-weight: 800;
      font-size: .78rem;
      letter-spacing: .02em;
      text-transform: uppercase;
      color: #334155;
      background: #f8fafc;
      position: sticky;
      top: 0;
      z-index: 10;
      border-bottom: 1px solid var(--line);
    }

    .tbl th,
    .tbl td {
      padding: .9rem 1rem;
    }

    .tbl tbody tr {
      transition: background .12s ease, transform .12s ease;
    }

    .tbl tbody tr:hover {
      background: #f9fafb;
    }

    .tbl tbody tr+tr td {
      border-top: 1px solid var(--line);
    }

    /* Tiny utility */
    .chip {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      padding: .25rem .5rem;
      font-size: .72rem;
      font-weight: 700;
      border-radius: .5rem;
      background: #eef2ff;
      color: #3730a3;
      border: 1px solid #c7d2fe;
    }

    /* Toast */
    .toast {
      position: fixed;
      right: 1rem;
      bottom: 1rem;
      z-index: 60;
      background: #111827;
      color: #fff;
      padding: .75rem 1rem;
      border-radius: .75rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, .18);
      opacity: 0;
      transform: translateY(10px);
      transition: .2s ease;
    }

    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }

    /* Subtle icon box */
    .iconbox {
      height: 42px;
      width: 42px;
      border-radius: 12px;
      background: #fff;
      border: 1px solid var(--line);
      display: grid;
      place-items: center;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
    }
  </style>
@endsection

@section('content')
  <div class="space-y-6">

    {{-- Header --}}
    <div class="shine rounded-xl">
      <div class="px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="iconbox">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M9 3a1 1 0 0 0-1 1v1H5.5a1 1 0 0 0-.96.72l-1.5 5A1 1 0 0 0 4 12h.5v7a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-7H20a1 1 0 0 0 .96-1.28l-1.5-5a1 1 0 0 0-.96-.72H16V4a1 1 0 0 0-1-1H9Zm2 10a1 1 0 1 1 2 0v4a1 1 0 1 1-2 0v-4Zm-3 0a1 1 0 1 1 2 0v4a1 1 0 1 1-2 0v-4Zm8 0a1 1 0 1 1 2 0v4a1 1 0 1 1-2 0v-4ZM10 5h4v1h-4V5Z" />
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-bold">Production Archive</h2>
            <p class="text-sm text-gray-500">Soft-deleted production records remain for 7 days before permanent purge.</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <a href="{{ route('production.index') }}" class="btn btn-secondary-blue">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-width="2" d="m10 19-7-7 7-7m-7 7h18" />
            </svg>
            Back
          </a>
          <a href="{{ route('production.archived') }}" class="btn btn-ghost">
            Refresh
          </a>
        </div>
      </div>
    </div>

    {{-- Toolbar --}}
    <div class="soft-card p-4">
      @php
        $sortVal = $sort ?? 'deleted_at';
        $qVal = $q ?? '';
        $sourceVal = $source ?? '';
      @endphp

      <form method="GET" action="{{ route('production.archived') }}"
        class="flex flex-col lg:flex-row gap-3 lg:items-end lg:justify-between">
        <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-3">
          {{-- Search --}}
          <div class="md:col-span-2">
            <label class="block text-xs text-gray-500 mb-1">Search</label>
            <div class="relative">
              <input type="text" name="q" value="{{ $qVal }}" placeholder="Search batch, product, type…"
                class="w-full rounded-lg border px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                style="border-color:var(--line)">
              <span class="absolute right-2 top-2.5 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor">
                  <circle cx="11" cy="11" r="7" stroke-width="2" />
                  <path d="m20 20-3.5-3.5" stroke-width="2" />
                </svg>
              </span>
            </div>
            <p class="text-[11px] text-gray-500 mt-1">Tip: press <span class="kbd">Enter</span> to search</p>
          </div>

          {{-- Sort --}}
          <div>
            <label class="block text-xs text-gray-500 mb-1">Sort by</label>
            <select name="sort"
              class="w-full rounded-lg border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
              style="border-color:var(--line)">
              <option value="deleted_at" {{ $sortVal === 'deleted_at' ? 'selected' : '' }}>Deleted Date</option>
              <option value="date" {{ $sortVal === 'date' ? 'selected' : '' }}>Production Date</option>
              <option value="product" {{ $sortVal === 'product' ? 'selected' : '' }}>Product</option>
              <option value="batch" {{ $sortVal === 'batch' ? 'selected' : '' }}>Batch</option>
              <option value="qty" {{ $sortVal === 'qty' ? 'selected' : '' }}>Quantity</option>
            </select>
          </div>

          {{-- Source filter --}}
          <div>
            <label class="block text-xs text-gray-500 mb-1">Archive Source</label>
            <select name="source"
              class="w-full rounded-lg border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
              style="border-color:var(--line)">
              <option value="" {{ $sourceVal === '' ? 'selected' : '' }}>All sources</option>
              <option value="sales" {{ $sourceVal === 'sales' ? 'selected' : '' }}>From Sales</option>
              <option value="production" {{ $sourceVal === 'production' ? 'selected' : '' }}>From Production</option>
              <option value="other" {{ $sourceVal === 'other' ? 'selected' : '' }}>Other / Manual</option>
            </select>
          </div>

          {{-- Buttons --}}
          <div class="flex items-end gap-2">
            <button type="submit" class="btn btn-secondary-green">Apply</button>
            <a href="{{ route('production.archived') }}" class="btn btn-ghost">Clear</a>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 mt-3 lg:mt-0">
          <span class="pill pill-gray">Archived</span>
          <span class="pill pill-amber">Purge in 30 days</span>
          <span class="pill pill-green">Restorable</span>
          <span class="pill pill-red">Delete Forever</span>
        </div>
      </form>
    </div>

    {{-- Bulk Actions --}}
    <div class="soft-card p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div class="text-sm text-gray-600">
        <span class="font-semibold">{{ $items->total() }}</span> archived record{{ $items->total() === 1 ? '' : 's' }}
      </div>
      <div class="flex items-center gap-2">
        <button id="bulkRestore" type="button" class="btn btn-secondary-green" disabled>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path fill="currentColor"
              d="M13 3a9 9 0 1 0 8.94 8H20a7 7 0 1 1-2.05-4.95L16 8h5V3l-1.64 1.64A8.96 8.96 0 0 0 13 3Z" />
          </svg>
          Bulk Restore
        </button>
        <button id="bulkDelete" type="button" class="btn btn-primary" disabled>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path fill="currentColor"
              d="M9 3h6a1 1 0 0 1 1 1v1h4v2H4V5h4V4a1 1 0 0 1 1-1Zm1 6h2v8h-2V9Zm4 0h2v8h-2V9Zm-8 0h2v8H6V9Z" />
          </svg>
          Bulk Delete Forever
        </button>
      </div>
    </div>

    {{-- Table --}}
    <div class="soft-card overflow-auto">
      <table class="tbl">
        <thead>
          <tr>
            <th class="px-4 py-3"><input type="checkbox" id="checkAll" class="h-4 w-4"></th>
            <th class="px-4 py-3">Batch</th>
            <th class="px-4 py-3">Product</th>
            <th class="px-4 py-3">Type</th>
            <th class="px-4 py-3">Source</th>
            <th class="px-4 py-3 text-right">Qty</th>
            <th class="px-4 py-3">Prod Date</th>
            <th class="px-4 py-3">Expiry</th>
            <th class="px-4 py-3">Deleted</th>
            <th class="px-4 py-3">Purge At</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $p)
            @php
              $reason = strtolower((string) ($p->archived_reason ?? ''));
              if (str_contains($reason, 'sale')) {
                $sourceLabel = 'From Sales';
                $sourceClass = 'pill-blue';
              } elseif (str_contains($reason, 'production')) {
                $sourceLabel = 'From Production';
                $sourceClass = 'pill-green';
              } elseif ($reason !== '') {
                $sourceLabel = ucfirst($p->archived_reason);
                $sourceClass = 'pill-gray';
              } else {
                $sourceLabel = 'Unknown';
                $sourceClass = 'pill-gray';
              }
            @endphp
            <tr>
              <td class="px-4 py-3 align-top">
                <input type="checkbox" class="rowCheck h-4 w-4" value="{{ $p->id }}">
              </td>
              <td class="px-4 py-3 align-top">
                <div class="font-bold tracking-wide">{{ $p->batch_number ?? '—' }}</div>
                <div class="text-[11px] text-gray-500">#{{ $p->id }}</div>
              </td>
              <td class="px-4 py-3 align-top">
                <div class="font-medium">
                  {{ $p->product?->product_name ?? '—' }}
                </div>

                @if($p->product?->parent)
                  <div class="text-xs text-gray-500">
                    Parent: {{ $p->product->parent->product_name }}
                  </div>
                @endif
              </td>

              <td class="px-4 py-3 align-top">
                <span class="chip">
                  {{ $p->product_name_snapshot ?? 'Base' }}
                </span>
              </td>
              <td class="px-4 py-3 align-top">
                <span class="pill {{ $sourceClass }}">{{ $sourceLabel }}</span>
              </td>
              <td class="px-4 py-3 align-top text-right">
                {{ number_format((float) ($p->quantity ?? 0), 2) }}
              </td>
              <td class="px-4 py-3 align-top">
                {{ $p->production_date ? \Carbon\Carbon::parse($p->production_date)->format('Y-m-d') : '—' }}
              </td>
              <td class="px-4 py-3 align-top">
                {{ $p->expiration_date ? \Carbon\Carbon::parse($p->expiration_date)->format('Y-m-d') : '—' }}
              </td>
              <td class="px-4 py-3 align-top">
                {{ $p->deleted_at ? \Carbon\Carbon::parse($p->deleted_at)->format('Y-m-d H:i') : '—' }}
              </td>
              <td class="px-4 py-3 align-top">
                @if(!empty($p->purge_at))
                  <span class="pill pill-amber">{{ \Carbon\Carbon::parse($p->purge_at)->diffForHumans() }}</span>
                @else
                  <span class="text-gray-400">—</span>
                @endif
              </td>
              <td class="px-4 py-3 align-top">
                <div class="flex items-center gap-2 justify-end">
                  <button type="button" class="btn btn-secondary-green px-3 py-1" data-action="restore"
                    data-id="{{ $p->id }}">
                    Restore
                  </button>
                  <button type="button" class="btn btn-primary px-3 py-1" data-action="force" data-id="{{ $p->id }}">
                    Delete Forever
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="px-4 py-10 text-center text-gray-500">
                No archived items found. 🎉
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    <div class="flex justify-end">
      {{ $items->withQueryString()->links() }}
    </div>
  </div>

  {{-- Toast --}}
  <div id="toast" class="toast" role="status" aria-live="polite"></div>
@endsection

@section('scripts')
  <script>
    (function () {
      const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
      const $ = (sel, root = document) => root.querySelector(sel);

      // --- CSRF fetch fallback ---
      if (!window.csrfFetch) {
        window.csrfFetch = function (url, opts = {}) {
          const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
          const method = (opts.method || 'GET').toUpperCase();
          const headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }, opts.headers || {});
          // For POST/DELETE without body, send FormData to satisfy Laravel
          let body = opts.body ?? null;
          if ((method === 'POST' || method === 'DELETE' || method === 'PATCH' || method === 'PUT') && body == null) {
            const fd = new FormData(); // empty payload still carries CSRF
            body = fd;
          }
          return fetch(url, Object.assign({}, opts, { method, headers, body }));
        }
      }

      const toast = $('#toast');
      function showToast(msg, ok = true) {
        if (!toast) return;
        toast.textContent = msg;
        toast.style.background = ok ? '#111827' : '#991b1b';
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2200);
      }

      const rowChecks = $$('.rowCheck');
      const checkAll = $('#checkAll');
      const bulkRestore = $('#bulkRestore');
      const bulkDelete = $('#bulkDelete');

      function syncBulkState() {
        const any = rowChecks.some(cb => cb.checked);
        if (bulkRestore) bulkRestore.disabled = !any;
        if (bulkDelete) bulkDelete.disabled = !any;
      }
      rowChecks.forEach(cb => cb.addEventListener('change', syncBulkState));
      checkAll?.addEventListener('change', function () {
        rowChecks.forEach(cb => cb.checked = checkAll.checked);
        syncBulkState();
      });

      async function doAction(id, kind) {
        // kind: 'restore' | 'force'
        const restoreUrl = "{{ route('production.restore', '__ID__') }}".replace('__ID__', id);
        const forceUrl = "{{ route('production.force', '__ID__') }}".replace('__ID__', id);
        const url = (kind === 'restore') ? restoreUrl : forceUrl;
        const method = (kind === 'restore') ? 'POST' : 'DELETE';

        try {
          const res = await window.csrfFetch(url, { method });
          let data;
          try { data = await res.json(); } catch (_) { data = { ok: res.ok, message: res.ok ? 'Done' : 'Failed' } }
          showToast(data.message || (res.ok ? 'Done' : 'Failed'), !!data.ok);
          if (res.ok && (data.ok ?? true)) {
            // Remove row
            const row = document.querySelector(`.rowCheck[value="${id}"]`)?.closest('tr');
            row?.remove();
            syncBulkState();
          }
        } catch (e) {
          showToast('Network error', false);
        }
      }

      // Single actions
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        if (!id || !action) return;

        if (action === 'force') {
          if (!confirm('This will permanently delete the record. Continue?')) return;
        }
        doAction(id, action);
      });

      // Bulk actions
      async function bulk(kind) {
        const ids = rowChecks.filter(cb => cb.checked).map(cb => cb.value);
        if (ids.length === 0) return;

        if (kind === 'force') {
          if (!confirm(`Permanently delete ${ids.length} record(s)? This cannot be undone.`)) return;
        }

        for (const id of ids) {
          await doAction(id, kind);
          await new Promise(r => setTimeout(r, 110));
        }
      }

      bulkRestore?.addEventListener('click', () => bulk('restore'));
      bulkDelete?.addEventListener('click', () => bulk('force'));
    })();
  </script>
@endsection