@extends('layout.mainlayout')

@section('head')
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body, p, ul, li, a, button { font-family: 'Jost', system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
    .wrap {
      min-height: 100vh;
      background: linear-gradient(135deg,#1F1E1E 0%, #001C00 100%);
      padding: 2.5rem 1.5rem;
    }
    .card {
      position: relative; overflow: hidden; border-radius: 20px;
      background: linear-gradient(135deg, rgba(31,30,30,.95), rgba(0,28,0,.75));
      border: .5px solid rgba(255,255,255,.2);
      box-shadow: 0 10px 32px rgba(0,0,0,.45);
      backdrop-filter: blur(8px);
      color: #fff;
      max-width: 760px; margin: 0 auto;
      padding: 2rem;
    }
    .card::before{
      content:''; position:absolute; inset:0; pointer-events:none;
      background: linear-gradient(45deg, rgba(4,119,5,.10), rgba(237,209,0,.10), rgba(4,119,5,.10));
      animation: cardShine 8s ease-in-out infinite;
    }
    @keyframes cardShine { 0%{opacity:.35} 50%{opacity:.18} 100%{opacity:.35} }
    .chip {
      display:inline-flex; align-items:center; gap:.4rem;
      padding:.25rem .6rem; font-size:.75rem; border-radius:999px;
      border:1px solid rgba(255,255,255,.2); background:rgba(255,255,255,.06);
    }
    .chip-ok      { border-color:#04770550; background:#04770526; color:#9AF2A8; }
    .chip-warn    { border-color:#EDD10050; background:#EDD10026; color:#FFE877; }
    .chip-bad     { border-color:#ef444450; background:#ef444426; color:#fecaca; }
    .btn-ghost{
      border:1px solid rgba(255,255,255,.15); color:#f8fafc;
      background: rgba(255,255,255,.06); border-radius:12px;
      padding:.55rem 1rem; transition:.2s;
    }
    .btn-primary{
      background: linear-gradient(90deg,#047705 0%, #0aad0a 100%);
      color:#fff; border:1px solid rgba(255,255,255,.15);
      border-radius:12px; padding:.55rem 1rem;
      box-shadow:0 6px 18px rgba(4,119,5,.35);
      transition:.2s;
    }
    .btn-primary:hover,.btn-ghost:hover{ transform: translateY(-1px); }
    .muted { color: rgba(255,255,255,.7); }
    .kv { display:flex; justify-content:space-between; gap:1rem; }
    .kv + .kv { margin-top:.4rem; }
    .sep { height:1px; background:rgba(255,255,255,.12); margin:1rem 0; }
    @media print { .no-print { display:none !important; } body { background:#fff; } .card { box-shadow:none; border:1px solid #e5e7eb; } }
  </style>
@endsection

@section('content')
<div class="wrap">
  <div class="card">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-2">
      <div>
        <h2 class="text-2xl font-bold" style="text-shadow:-2px 1px 0px #047705;">Receipt</h2>
        <p class="text-sm muted">Thank you for your purchase.</p>
      </div>
      <span class="text-xs sm:text-sm chip">
        {{ $meta['invoice'] ?? 'No-INV' }}
      </span>
    </div>

    {{-- Customer + Status --}}
    <div class="kv">
      <div class="muted">Customer</div>
      <div>{{ $meta['customer_name'] ?? '—' }}</div>
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
      <div class="font-medium">{{ $meta['display_product'] }}</div>
    </div>
    <div class="kv">
      <div class="muted">Order Date</div>
      <div>{{ $meta['order_date'] ? \Carbon\Carbon::parse($meta['order_date'])->format('F j, Y') : '—' }}</div>
    </div>
    <div class="kv">
      <div class="muted">Quantity</div>
      <div>{{ number_format((float)$meta['quantity'], 3) }} kg</div>
    </div>
    <div class="kv">
      <div class="muted">Unit Price</div>
      <div>₱{{ number_format((float)$meta['unit_price'], 2) }}</div>
    </div>
    <div class="sep"></div>
    <div class="kv" style="font-size:1.1rem">
      <div class="font-semibold">Total</div>
      <div class="font-bold">₱{{ number_format((float)$meta['total'], 2) }}</div>
    </div>

    <div class="sep"></div>

    {{-- Production + Expiration --}}
    <div class="kv">
      <div class="muted">Batch No.</div>
      <div>{{ $meta['batch_number'] ?? '—' }}</div>
    </div>
    <div class="kv">
      <div class="muted">Production Date</div>
      <div>{{ $meta['production_date'] ? \Carbon\Carbon::parse($meta['production_date'])->format('F j, Y') : '—' }}</div>
    </div>
    <div class="kv">
      <div class="muted">Expiration Date</div>
      @php
        $days = $meta['days_left'];
        $badge = $days === null ? '' : ($days < 0 ? 'chip-bad' : ($days <= 3 ? 'chip-warn' : 'chip-ok'));
        $label = $days === null ? '' : ($days < 0 ? abs($days).' days past' : $days.' days left');
      @endphp
      <div>
        {{ $meta['expiration_date'] ? \Carbon\Carbon::parse($meta['expiration_date'])->format('F j, Y') : '—' }}
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

    {{-- Footer actions --}}
    @if(!empty($lastAudit))
  <p class="text-xs text-white/60 mt-2">
    Last edit: {{ optional($lastAudit->created_at)->format('Y-m-d H:i') }}
    @if($lastAudit->user_id) by #{{ $lastAudit->user_id }} @endif
  </p>
@endif

  </div>
</div>
@endsection
