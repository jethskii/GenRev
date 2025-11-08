@extends('layout.mainlayout')

@section('head')
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Typography */
    body, p, ul, li, a, button { font-family: 'Jost', system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }

    /* Page wrapper (light, airy) */
    .wrap {
      min-height: 100vh;
      background: #f7f8fb; /* off-white page bg (matches layout) */
      padding: 2.5rem 1.5rem;
    }

    /* Card (white with subtle border/shadow to match Sales/Production light cards) */
    .card {
      position: relative; overflow: hidden; border-radius: 20px;
      background: #ffffff;
      border: 1px solid #e5e7eb;
      box-shadow: 0 8px 18px rgba(17,24,39,.04);
      color: #111827; /* gray-900 */
      max-width: 760px; margin: 0 auto;
      padding: 2rem;
    }

    /* Key/value rows */
    .kv { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; }
    .kv + .kv { margin-top:.5rem; }
    .muted { color: #6b7280; } /* gray-500 */
    .sep { height:1px; background:#e5e7eb; margin:1rem 0; }

    /* Chips (status badges, light theme) */
    .chip {
      display:inline-flex; align-items:center; gap:.4rem;
      padding:.28rem .6rem; font-size:.75rem; font-weight:600; border-radius:999px;
      border:1px solid #e5e7eb; background:#f9fafb; color:#111827;
      white-space: nowrap;
    }
    .chip-ok   { background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }   /* green */
    .chip-warn { background:#fffbeb; border-color:#fcd34d; color:#92400e; }   /* amber/yellow */
    .chip-bad  { background:#fef2f2; border-color:#fecaca; color:#991b1b; }   /* red */

    /* Unit-type chip */
    .u-chip{
      display:inline-flex; align-items:center; gap:.35rem;
      padding:.18rem .5rem; border-radius:999px; font-size:.7rem; font-weight:600;
      border:1px solid #e5e7eb; background:#f8fafc; color:#334155;
      margin-left:.4rem;
    }

    /* Buttons: align with layout (red primary; optional ghost) */
    .btn { font-weight:600; border-radius:.75rem; padding:.55rem 1rem; border:1px solid transparent; }
    .btn:disabled{ opacity:.6; cursor:not-allowed; }
    .btn-secondary-blue{ background:#2563eb; color:#fff; }
    .btn-secondary-blue:hover{ filter:brightness(1.05); }
    .btn-ghost{ background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; }
    .btn-ghost:hover{ background:#e5e7eb; }

    /* Mono */
    .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }

    /* Print */
    @media print {
      .no-print { display:none !important; }
      body { background:#fff; }
      .wrap { padding:0; }
      .card { box-shadow:none; border:1px solid #e5e7eb; border-radius:0; }
    }
  </style>
@endsection

@section('content')
@php
  // Defensive meta extraction
  $invoiceNumber   = $meta['invoice'] ?? $sale->invoice_number ?? 'No-INV';
  $customerName    = $meta['customer_name'] ?? $sale->customer_name ?? '—';
  $statusRaw       = $meta['status'] ?? $sale->status ?? \App\Models\Sale::STATUS_COMPLETED;
  $statusLower     = strtolower((string)$statusRaw);

  // Status chip class
  $statusClass = $statusLower === 'paid' ? 'chip-ok'
               : ($statusLower === 'pending' ? 'chip-warn'
               : ($statusLower === 'cancelled' ? 'chip-bad' : ''));

  // Display product
  $displayProduct  = $meta['display_product'] ?? $sale->product ?? optional($sale->productRef)->product_name ?? '—';

  // Dates
  $orderDate       = $meta['order_date']      ?? $sale->date ?? $sale->order_date ?? null;
  $productionDate  = $meta['production_date'] ?? optional($sale->productionRef)->production_date ?? null;
  $expirationDate  = $meta['expiration_date'] ?? optional($sale->productionRef)->expiration_date ?? null;

  // Quantity / pricing
  $qtyKg           = (float)($meta['quantity'] ?? $sale->quantity ?? 0);      // stored in kg
  $unitPrice       = (float)($meta['unit_price'] ?? $sale->price ?? $sale->unit_price ?? 0);

  // Unit type chip if relevant (pack/bag)
  $unitTypeRaw     = $sale->unit_type ?? $sale->unit ?? ($meta['unit_type'] ?? null);
  $unitTypeChip    = in_array($unitTypeRaw, ['pack','bag'], true) ? $unitTypeRaw : null;

  // Total (re-compute if not provided to keep the receipt accurate)
  $totalFromMeta   = isset($meta['total']) ? (float)$meta['total'] : null;
  $computedTotal   = round($qtyKg * $unitPrice, 2);
  $totalAmount     = is_null($totalFromMeta) ? $computedTotal : (float)$totalFromMeta;

  // Batch information
  $batchNumber     = $meta['batch_number'] ?? optional($sale->productionRef)->batch_number ?? '—';

  // Days left label if we have an expiration date
  $daysLeft        = $meta['days_left'] ?? (function($exp){
                        if (!$exp) return null;
                        try {
                          $d = \Carbon\Carbon::parse($exp)->startOfDay();
                          return now()->startOfDay()->diffInDays($d, false);
                        } catch (\Throwable $e) { return null; }
                      })($expirationDate);

  $daysBadgeClass  = is_null($daysLeft) ? '' : ($daysLeft < 0 ? 'chip-bad' : ($daysLeft <= 3 ? 'chip-warn' : 'chip-ok'));
  $daysBadgeText   = is_null($daysLeft) ? '' : ($daysLeft < 0 ? abs($daysLeft).' days past' : $daysLeft.' days left');

  // Notes / Internal remarks
  $publicNotes     = trim((string)($sale->notes ?? ''));
  $internalRemarks = $meta['remarks'] ?? $sale->internal_notes ?? $sale->remarks ?? null;
@endphp

<div class="wrap">
  <div class="card">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-3">
      <div>
        <h2 class="text-2xl font-bold">Invoice</h2>
        <p class="text-sm muted">Thank you for your purchase.</p>
      </div>
      <span class="text-xs sm:text-sm chip mono">
        {{ $invoiceNumber }}
      </span>
    </div>

    {{-- Customer + Status --}}
    <div class="kv">
      <div class="muted">Customer</div>
      <div class="font-medium">{{ $customerName }}</div>
    </div>
    <div class="kv">
      <div class="muted">Status</div>
      <div class="chip {{ $statusClass }}">{{ ucfirst($statusRaw) }}</div>
    </div>

    <div class="sep"></div>

    {{-- Product + Pricing --}}
    <div class="kv">
      <div class="muted">Product</div>
      <div class="font-semibold">{{ $displayProduct }}</div>
    </div>
    <div class="kv">
      <div class="muted">Order Date</div>
      <div>{{ $orderDate ? \Carbon\Carbon::parse($orderDate)->format('F j, Y') : '—' }}</div>
    </div>
    <div class="kv">
      <div class="muted">Quantity</div>
      <div class="mono">{{ number_format((float)$qtyKg, 3) }} kg</div>
    </div>
    <div class="kv">
      <div class="muted">Unit Price</div>
      <div class="mono">
        ₱{{ number_format((float)$unitPrice, 2) }}
        @if($unitTypeChip)
          <span class="u-chip">per {{ $unitTypeChip }}</span>
        @endif
      </div>
    </div>

    <div class="sep"></div>

    <div class="kv" style="font-size:1.1rem">
      <div class="font-semibold">Total</div>
      <div class="font-extrabold mono">₱{{ number_format($totalAmount, 2) }}</div>
    </div>

    <div class="sep"></div>

    {{-- Production + Expiration --}}
    <div class="kv">
      <div class="muted">Batch No.</div>
      <div class="mono">{{ $batchNumber }}</div>
    </div>
    <div class="kv">
      <div class="muted">Production Date</div>
      <div>{{ $productionDate ? \Carbon\Carbon::parse($productionDate)->format('F j, Y') : '—' }}</div>
    </div>
    <div class="kv">
      <div class="muted">Expiration Date</div>
      <div>
        {{ $expirationDate ? \Carbon\Carbon::parse($expirationDate)->format('F j, Y') : '—' }}
        @if(!is_null($daysLeft))
          <span class="chip {{ $daysBadgeClass }}" style="margin-left:.5rem">{{ $daysBadgeText }}</span>
        @endif
      </div>
    </div>

    {{-- Notes (if any) --}}
    @if($publicNotes !== '')
      <div class="sep"></div>
      <div class="kv">
        <div class="muted">Notes</div>
        <div style="max-width:520px">{{ $publicNotes }}</div>
      </div>
    @endif

    {{-- Remarks / Internal notes (if any) --}}
    @php $internalRemarks = is_string($internalRemarks) ? trim($internalRemarks) : $internalRemarks; @endphp
    @if(!empty($internalRemarks))
      <div class="sep"></div>
      <div class="kv">
        <div class="muted">
          Remarks <span class="u-chip">internal</span>
        </div>
        <div style="max-width:520px">{{ $internalRemarks }}</div>
      </div>
    @endif

    {{-- Audit (muted, light) --}}
    @if(!empty($lastAudit))
      <p class="text-xs text-gray-500 mt-3">
        Last edit: {{ optional($lastAudit->created_at)->format('Y-m-d H:i') }}
        @if($lastAudit->user_id) by #{{ $lastAudit->user_id }} @endif
      </p>
    @endif

    {{-- Actions --}}
    <div class="mt-4 flex items-center gap-2 no-print">
      <button onclick="window.print()" class="btn btn-secondary-blue">Print</button>
      <a href="{{ url()->previous() }}" class="btn btn-ghost">Back</a>
    </div>
  </div>
</div>
@endsection
