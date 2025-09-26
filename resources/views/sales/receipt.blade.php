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

    /* Buttons: align with layout (red primary; optional ghost) */
    .btn { font-weight:600; border-radius:.75rem; padding:.55rem 1rem; border:1px solid transparent; }
    .btn:disabled{ opacity:.6; cursor:not-allowed; }
    .btn-primary{ background:#ef4444; color:#fff; box-shadow:0 6px 14px rgba(239,68,68,.18); }
    .btn-primary:hover{ filter:brightness(1.05); }
    .btn-ghost{ background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; }
    .btn-ghost:hover{ background:#e5e7eb; }

    /* Tiny utilities */
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
<div class="wrap">
  <div class="card">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-3">
      <div>
        <h2 class="text-2xl font-bold">Receipt</h2>
        <p class="text-sm muted">Thank you for your purchase.</p>
      </div>
      <span class="text-xs sm:text-sm chip mono">
        {{ $meta['invoice'] ?? 'No-INV' }}
      </span>
    </div>

    {{-- Customer + Status --}}
    <div class="kv">
      <div class="muted">Customer</div>
      <div class="font-medium">{{ $meta['customer_name'] ?? '—' }}</div>
    </div>
    <div class="kv">
      <div class="muted">Status</div>
      @php
        $st = strtolower($meta['status'] ?? 'completed');
        $cls = $st === 'paid' ? 'chip-ok' : ($st === 'pending' ? 'chip-warn' : ($st === 'cancelled' ? 'chip-bad' : ''));
      @endphp
      <div class="chip {{ $cls }}">{{ ucfirst($meta['status'] ?? 'Completed') }}</div>
    </div>

    <div class="sep"></div>

    {{-- Product + Pricing --}}
    <div class="kv">
      <div class="muted">Product</div>
      <div class="font-semibold">{{ $meta['display_product'] ?? '—' }}</div>
    </div>
    <div class="kv">
      <div class="muted">Order Date</div>
      <div>{{ !empty($meta['order_date']) ? \Carbon\Carbon::parse($meta['order_date'])->format('F j, Y') : '—' }}</div>
    </div>
    <div class="kv">
      <div class="muted">Quantity</div>
      <div class="mono">{{ number_format((float)($meta['quantity'] ?? 0), 3) }} kg</div>
    </div>
    <div class="kv">
      <div class="muted">Unit Price</div>
      <div class="mono">₱{{ number_format((float)($meta['unit_price'] ?? 0), 2) }}</div>
    </div>

    <div class="sep"></div>

    <div class="kv" style="font-size:1.1rem">
      <div class="font-semibold">Total</div>
      <div class="font-extrabold mono">₱{{ number_format((float)($meta['total'] ?? 0), 2) }}</div>
    </div>

    <div class="sep"></div>

    {{-- Production + Expiration --}}
    <div class="kv">
      <div class="muted">Batch No.</div>
      <div class="mono">{{ $meta['batch_number'] ?? '—' }}</div>
    </div>
    <div class="kv">
      <div class="muted">Production Date</div>
      <div>{{ !empty($meta['production_date']) ? \Carbon\Carbon::parse($meta['production_date'])->format('F j, Y') : '—' }}</div>
    </div>
    <div class="kv">
      <div class="muted">Expiration Date</div>
      @php
        $days = $meta['days_left'] ?? null;
        $badge = $days === null ? '' : ($days < 0 ? 'chip-bad' : ($days <= 3 ? 'chip-warn' : 'chip-ok'));
        $label = $days === null ? '' : ($days < 0 ? abs($days).' days past' : $days.' days left');
      @endphp
      <div>
        {{ !empty($meta['expiration_date']) ? \Carbon\Carbon::parse($meta['expiration_date'])->format('F j, Y') : '—' }}
        @if($days !== null)
          <span class="chip {{ $badge }}" style="margin-left:.5rem">{{ $label }}</span>
        @endif
      </div>
    </div>

    {{-- Notes (if any) --}}
    @if(!empty($sale->notes))
      <div class="sep"></div>
      <div class="kv">
        <div class="muted">Notes</div>
        <div style="max-width:520px">{{ $sale->notes }}</div>
      </div>
    @endif

    {{-- Audit (muted, light) --}}
    @if(!empty($lastAudit))
      <p class="text-xs text-gray-500 mt-3">
        Last edit: {{ optional($lastAudit->created_at)->format('Y-m-d H:i') }}
        @if($lastAudit->user_id) by #{{ $lastAudit->user_id }} @endif
      </p>
    @endif

    {{-- Optional actions (print / close) --}}
    <div class="mt-4 flex items-center gap-2 no-print">
      <button onclick="window.print()" class="btn btn-secondary-blue">Print</button>
      <a href="{{ url()->previous() }}" class="btn btn-ghost">Back</a>
    </div>
  </div>
</div>
@endsection
