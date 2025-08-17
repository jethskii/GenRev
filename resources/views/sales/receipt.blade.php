@extends('layout.mainlayout')

@section('head')
  {{-- Fonts + local styles to match Cooperatiba liquid theme --}}
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body, p, ul, li, a, button { font-family: 'Jost', system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }

    .liquid-wrap {
      min-height: 100vh;
      background: linear-gradient(135deg,#1F1E1E 0%, #001C00 100%);
    }
    .liquid-card {
      position: relative; overflow: hidden; border-radius: 20px;
      background: linear-gradient(135deg, rgba(31,30,30,.95), rgba(0,28,0,.75));
      border: .5px solid rgba(255,255,255,.2);
      box-shadow: 0 10px 32px rgba(0,0,0,.45);
      backdrop-filter: blur(8px);
    }
    .liquid-card::before{
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
    .chip-paid      { border-color:#04770550; background:#04770526; color:#9AF2A8; }
    .chip-pending   { border-color:#EDD10050; background:#EDD10026; color:#FFE877; }
    .chip-cancelled { border-color:#ef444450; background:#ef444426; color:#fecaca; }
    .chip-default   { border-color:#94a3b850; background:#94a3b826; color:#e5e7eb; }

    .btn-ghost{
      border:1px solid rgba(255,255,255,.15); color:#f8fafc;
      background: rgba(255,255,255,.06); border-radius:12px;
      padding:.55rem 1rem; transition:.2s;
    }
    .btn-ghost:hover{ background:rgba(255,255,255,.1); transform: translateY(-1px); }
    .btn-primary{
      background: linear-gradient(90deg,#047705 0%, #0aad0a 100%);
      color:#fff; border:1px solid rgba(255,255,255,.15);
      border-radius:12px; padding:.55rem 1rem;
      box-shadow:0 6px 18px rgba(4,119,5,.35);
      transition:.2s;
    }
    .btn-primary:hover{ transform: translateY(-1px); }

    @media print {
      .no-print { display:none !important; }
      body { background:#fff; }
      .liquid-card { box-shadow:none; border:1px solid #e5e7eb; }
    }
  </style>
@endsection

@section('content')
<div class="liquid-wrap py-10 px-6">
  <div class="max-w-2xl mx-auto liquid-card p-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-white" style="text-shadow:-2px 1px 0px #047705;">Receipt</h2>
        <p class="text-sm text-white/60">Thank you for your purchase.</p>
      </div>
      <span class="text-xs sm:text-sm bg-[#047705]/20 border border-[#047705]/40 text-white px-3 py-1 rounded-full">
        {{ $sale->invoice_number }}
      </span>
    </div>

    {{-- Body --}}
    <div class="space-y-4 text-base text-white">
      <p>
        <span class="text-white/70">Product:</span>
        <span class="font-medium">
          {{ optional($sale->product)->product_name ?? 'N/A' }}
        </span>
      </p>

      <p>
        <span class="text-white/70">Date:</span>
        {{ \Carbon\Carbon::parse($sale->date)->format('F j, Y') }}
      </p>

      <p>
        <span class="text-white/70">Quantity:</span>
        {{ (int) $sale->quantity }}
      </p>

      <p>
        <span class="text-white/70">Unit Price:</span>
        ₱{{ number_format((float) $sale->price, 2) }}
      </p>

      <div class="h-px w-full bg-white/10 my-4"></div>

      <p class="flex items-center justify-between text-lg">
        <span class="font-semibold">Total</span>
        <span class="font-bold">
          ₱{{ number_format((float) ($sale->total ?? ($sale->quantity * $sale->price)), 2) }}
        </span>
      </p>

      <p class="mt-2">
        <span class="text-white/70 mr-2">Status:</span>
        @php
          $status = strtolower($sale->status ?? '');
          $chipClass = match($status){
            'paid'      => 'chip-paid',
            'pending'   => 'chip-pending',
            'cancelled' => 'chip-cancelled',
            default     => 'chip-default',
          };
        @endphp
        <span class="chip {{ $chipClass }}">
          {{ ucfirst($status ?: 'unknown') }}
        </span>
      </p>
    </div>

    {{-- Footer actions --}}
    <div class="no-print flex flex-col sm:flex-row gap-3 items-center justify-between mt-8">
      <a href="{{ route('sales') }}" class="btn-ghost">
        ← Back to Sales
      </a>
      <div class="flex gap-2">
        <button onclick="window.print()" class="btn-primary">
          🖨️ Print
        </button>
      </div>
    </div>

  </div>
</div>
@endsection
