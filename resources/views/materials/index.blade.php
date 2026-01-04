{{-- resources/views/materials/index.blade.php --}}
@extends('layout.mainlayout')

@section('content')
@php
  $isPaginated = $materials instanceof \Illuminate\Contracts\Pagination\Paginator;
  $rows        = $isPaginated ? $materials->items() : $materials;

  $currentQuery = request()->query();
  $sort    = request('sort', 'name_asc');
  $perPage = request('per_page', 50);
  $search  = trim((string) request('search', ''));
  $cat     = request('category');
  $lowOnly = (bool) request()->boolean('low_stock', false);

  $sortOptions = [
    'name_asc'=>'Name ↑','name_desc'=>'Name ↓','qty_desc'=>'Quantity ↓','qty_asc'=>'Quantity ↑',
    'price_desc'=>'Unit Price ↓','price_asc'=>'Unit Price ↑',
    'updated_desc'=>'Updated ↓','updated_asc'=>'Updated ↑',
  ];

  $unitOptions = [
    'kg'=>'Kilograms','g'=>'Grams','lbs'=>'Pounds','pcs'=>'Pieces','pkg'=>'Package',
    'box'=>'Box','bag'=>'Bag','roll'=>'Roll','tray'=>'Tray','lt'=>'Liters','ml'=>'Milliliters','m3'=>'Cubic Meter'
  ];

  $categoryCatalog = [
    'Primary Raw Materials','Meat Cuts & Trimmings','Fats / Skins','Salt','Curing Agents (Nitrite/Nitrate)',
    'Phosphates','Spices & Seasonings','Fillers & Binders','Sugars','Water / Ice','Smoke Materials',
    'Casings','Packaging Films & Bags','Labels & Cartons','Cleaning & Sanitation (Non-food)',
  ];

  function infer_category($m){
    $name = strtolower(($m->material_name ?? ''));
    $map = [
      'Primary Raw Materials'=>['carcass','beef','pork','chicken','lamb','whole'],
      'Meat Cuts & Trimmings'=>['loin','belly','ham','shoulder','trim','cut'],
      'Fats / Skins'=>['fat','tallow','skin','backfat'],
      'Salt'=>['salt','sodium chloride'],
      'Curing Agents (Nitrite/Nitrate)'=>['nitrite','nitrate','prague powder','curing'],
      'Phosphates'=>['phosphate'],
      'Spices & Seasonings'=>['pepper','garlic','spice','season','paprika','herb'],
      'Fillers & Binders'=>['starch','soy','binder','breadcrumb','flour','tvp'],
      'Sugars'=>['sugar','dextrose','sucrose'],
      'Water / Ice'=>['water','ice'],
      'Smoke Materials'=>['smoke','wood chip','hickory','liquid smoke'],
      'Casings'=>['casing','collagen','hog casing','sheep casing'],
      'Packaging Films & Bags'=>['vacuum','film','bag','tray','shrink','roll'],
      'Labels & Cartons'=>['label','carton','box','sticker'],
      'Cleaning & Sanitation (Non-food)'=>['sanitizer','detergent','glove','hairnet','apron'],
    ];
    foreach($map as $cat => $needles){
      foreach($needles as $n){ if(str_contains($name,$n)) return $cat; }
    }
    return 'Primary Raw Materials';
  }

  // valuation uses unit_price * quantity_kg
  $stats = (function($items){
    $sum=0;$cnt=0;$low=0;
    foreach($items as $m){
      $qty=(float)($m->quantity_kg??0);
      $price=(float)($m->unit_price??0);
      $sum += $qty*$price; $cnt++;
      $min=(float)($m->min_stock_kg??-1); if($min>=0 && $qty<$min) $low++;
    }
    return ['count'=>$cnt,'valuation'=>$sum,'low'=>$low];
  })($rows);

  // === Predictive usage data from controller ===
  $sparkData = (isset($sparkData) && is_array($sparkData) && count($sparkData) > 0)
      ? $sparkData
      : [0,0,0,0,0,0];

  $avg30 = (float)($avg30 ?? 0.0);   // 30-day baseline daily usage (kg)
  $avg7  = (float)($avg7  ?? 0.0);   // last 7 days daily usage (kg)

  $trendUp        = $avg7 >= $avg30;
  $predictedNext7 = $avg7;
@endphp

<style>
  :root{
    --bg-body:#f5f5f6;
    --panel-bg:#fdfdfc;
    --border-strong:#111827;
    --shadow-main:0 4px 0 #111827;
    --accent-red:#b91c1c;
    --accent-red-soft:#fecaca;
    --accent-green:#16a34a;
    --accent-amber:#fbbf24;
    --text-main:#111827;
    --text-muted:#6b7280;
    --text-soft:#9ca3af;
    --table-row-odd:#fef2f2;
    --table-row-even:#fff7ed;
    --kpi-bg:#f9fafb;
  }

  html{ scroll-behavior:smooth; }
  body{
    font-family:system-ui,-apple-system,BlinkMacSystemFont,"SF Pro Text","Segoe UI",sans-serif;
  }

  /* === PAGE / PANEL === */
  .page-wrap{
    padding:1.5rem;
    background:var(--bg-body);
  }
  .pixel-panel{
    max-width:1200px;
    margin:0 auto;
    background:var(--panel-bg);
    border:1px solid rgba(15,23,42,0.18);
    box-shadow:0 14px 30px rgba(15,23,42,0.08);
    padding:1.5rem 1.6rem 1.6rem;
    border-radius:14px;
    font-size:12px;
    color:var(--text-main);
    transition:box-shadow .18s ease,transform .18s ease;
  }
  .pixel-panel:hover{
    box-shadow:0 18px 40px rgba(15,23,42,0.12);
    transform:translateY(-2px);
  }

  /* === HEADER / TAGS === */
  .pixel-title{
    font-size:18px;
    font-weight:700;
    line-height:1.4;
  }
  .pixel-title span{
    display:inline-block;
    padding:2px 8px;
    background:#fee2e2;
    border-radius:999px;
    color:var(--accent-red);
    font-size:11px;
    margin-left:4px;
  }
  .pixel-subtitle{
    margin-top:4px;
    color:var(--text-muted);
    font-size:12px;
  }
  .pixel-tag{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:4px 10px;
    background:#fef2f2;
    border-radius:999px;
    border:1px solid rgba(185,28,28,.18);
    color:var(--accent-red);
    font-size:11px;
    font-weight:600;
  }
  .pixel-tag-dot{
    width:9px;
    height:9px;
    border-radius:999px;
    background:var(--accent-green);
    box-shadow:0 0 0 2px rgba(34,197,94,0.35);
  }
  .pixel-breadcrumb{
    margin-top:6px;
    display:flex;
    flex-wrap:wrap;
    gap:4px;
    color:var(--text-soft);
    font-size:11px;
  }
  .pixel-link{
    color:#1d4ed8;
    text-decoration:none;
  }
  .pixel-link:hover{
    text-decoration:underline;
  }
  .pixel-footnote{
    margin-top:.7rem;
    font-size:11px;
    color:var(--text-soft);
  }

  /* === BUTTONS === */
  .btn{
    position:relative;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:.35rem;
    padding:7px 14px;
    border-radius:999px;
    border:1px solid rgba(15,23,42,0.45);
    background:#f9fafb;
    cursor:pointer;
    text-transform:none;
    font-size:11px;
    font-weight:600;
    transition:background .12s ease,transform .12s ease,box-shadow .12s ease;
  }
  .btn svg{ width:14px;height:14px; }
  .btn:hover{
    background:#e5e7eb;
    transform:translateY(-1px);
    box-shadow:0 4px 10px rgba(15,23,42,0.12);
  }
  .btn:active{ transform:translateY(0); box-shadow:none; }
  .btn-primary{
    background:var(--accent-red);
    color:#fef2f2;
    border-color:var(--accent-red);
  }
  .btn-primary:hover{ background:#991b1b; }
  .btn-green{
    background:var(--accent-green);
    color:#ecfdf3;
    border-color:var(--accent-green);
  }
  .btn-green:hover{ background:#15803d; }
  .btn-ghost{
    background:#ffffff;
    color:var(--text-main);
  }

  /* === KPI CARDS === */
  .pixel-kpi-wrap{
    display:grid;
    grid-template-columns:repeat(1,minmax(0,1fr));
    gap:0.9rem;
  }
  @media (min-width:640px){
    .pixel-kpi-wrap{
      grid-template-columns:repeat(4,minmax(0,1fr));
    }
  }
  .pixel-kpi{
    border-radius:12px;
    border:1px solid rgba(15,23,42,0.08);
    background:var(--kpi-bg);
    padding:.8rem .9rem;
    display:flex;
    flex-direction:column;
    gap:4px;
  }
  .pixel-kpi-label{
    font-size:11px;
    color:var(--text-soft);
    text-transform:uppercase;
    letter-spacing:.04em;
  }
  .pixel-kpi-value{
    font-size:20px;
    font-weight:700;
    color:var(--accent-red);
  }

  /* === MINI SPARK BAR GRAPH === */
  .spark-card{
    display:flex;
    flex-direction:column;
    gap:4px;
  }
  .spark-bars{
    display:flex;
    align-items:flex-end;
    gap:3px;
    height:32px;
    margin-top:2px;
  }
  .spark-bar{
    flex:1;
    border-radius:4px 4px 0 0;
    background:#fed7aa;
    transition:transform .15s ease,background .15s ease,opacity .15s ease;
  }
  .spark-bar-strong{
    background:#b45309;
  }
  .spark-card:hover .spark-bar{
    transform:translateY(-1px);
  }
  .spark-caption{
    font-size:11px;
    color:var(--text-muted);
  }
  .spark-caption span{
    font-weight:600;
    color:var(--accent-green);
  }
  .spark-caption span.spark-worse{
    color:var(--accent-red);
  }

  /* === INPUTS / SELECT === */
  .input-light{
    width:100%;
    padding:6px 8px;
    border-radius:8px;
    border:1px solid rgba(15,23,42,0.18);
    background:#ffffff;
    font:inherit;
    font-size:12px;
    transition:border-color .15s ease,box-shadow .15s ease,background .15s ease;
  }
  .input-light::placeholder{ color:var(--text-soft); }
  .input-light:focus{
    outline:none;
    border-color:var(--accent-red);
    box-shadow:0 0 0 1px rgba(185,28,28,0.4);
    background:#fef2f2;
  }
  label{
    font-size:11px;
    color:var(--text-muted);
  }

  /* === CHIPS / BADGES === */
  .chip,
  .badge{
    display:inline-flex;
    align-items:center;
    padding:4px 10px;
    font-size:11px;
    border-radius:999px;
    border:1px solid rgba(15,23,42,0.12);
    background:#f9fafb;
    color:#111827;
    text-decoration:none;
    transition:background .12s ease,box-shadow .12s ease,transform .12s ease;
  }
  .chip:hover,
  .badge:hover{
    background:#f3f4f6;
    transform:translateY(-1px);
    box-shadow:0 2px 6px rgba(15,23,42,0.12);
  }
  .b-green{
    background:#ecfdf3;
    border-color:rgba(22,163,74,0.25);
    color:#14532d;
  }
  .b-amber{
    background:#fffbeb;
    border-color:rgba(245,158,11,0.25);
    color:#92400e;
  }
  .b-blue{
    background:#eff6ff;
    border-color:rgba(37,99,235,0.25);
    color:#1d4ed8;
  }
  .b-gray{
    background:#f3f4f6;
    color:#111827;
  }
  .b-red{
    background:#fee2e2;
    border-color:rgba(220,38,38,0.25);
    color:#b91c1c;
  }

  /* === TABLE === */
  .pixel-table-wrap{
    border-radius:14px;
    border:1px solid rgba(15,23,42,0.14);
    box-shadow:0 10px 26px rgba(15,23,42,0.08);
    background:#ffffff;
    overflow:hidden;
  }
  table.pixel-table{
    border-collapse:separate;
    border-spacing:0;
    width:100%;
    font-size:12px;
  }
  .pixel-table thead th{
    padding:8px 10px;
    background:#f9fafb;
    border-bottom:1px solid rgba(15,23,42,0.14);
    text-transform:uppercase;
    font-size:11px;
    color:var(--text-soft);
    font-weight:600;
  }
  .pixel-table tbody td{
    padding:7px 10px;
    border-bottom:1px solid #f3f4f6;
  }
  .pixel-table tbody tr:nth-child(odd){
    background:var(--table-row-odd);
  }
  .pixel-table tbody tr:nth-child(even){
    background:var(--table-row-even);
  }
  .pixel-table tbody tr:hover{
    background:#fee2e2;
  }
  .pixel-table tfoot td{
    padding:9px 10px;
    border-top:1px solid rgba(15,23,42,0.16);
    background:#f9fafb;
    font-weight:600;
    color:var(--accent-red);
  }

  .status-dot{
    width:9px;
    height:9px;
    border-radius:999px;
    flex-shrink:0;
  }
  .table-actions{
    display:flex;
    align-items:center;
    justify-content:center;
    flex-wrap:wrap;
    gap:.4rem;
  }

  .material-row{
    cursor:pointer;
  }

  /* === MODALS === */
  dialog.modal{
    border:0;
    padding:0;
    background:transparent;
    z-index:60;
  }
  dialog::backdrop{
    background:rgba(15,23,42,0.45);
    -webkit-backdrop-filter:blur(3px);
    backdrop-filter:blur(3px);
  }
  dialog[open]{ display:block; }
  .modal-box{
    background:#ffffff;
    border-radius:14px;
    border:1px solid rgba(15,23,42,0.15);
    box-shadow:0 18px 50px rgba(15,23,42,0.3);
    padding:1.1rem 1.25rem 1.25rem;
    max-height:80vh;
    overflow-y:auto;
  }
  .modal-title{
    font-size:14px;
    font-weight:700;
    color:var(--accent-red);
    margin-bottom:.9rem;
  }

  /* === DETAIL SLIDE-IN PANEL === */
  .detail-overlay{
    position:fixed;
    inset:0;
    background:rgba(15,23,42,0.35);
    -webkit-backdrop-filter:blur(3px);
    backdrop-filter:blur(3px);
    display:flex;
    justify-content:flex-end;
    opacity:0;
    pointer-events:none;
    transition:opacity .18s ease;
    z-index:70;
  }
  .detail-overlay.open{
    opacity:1;
    pointer-events:auto;
  }
  .detail-panel{
    width:340px;
    max-width:90vw;
    height:100%;
    background:#ffffff;
    border-left:1px solid rgba(15,23,42,0.2);
    box-shadow:-10px 0 30px rgba(15,23,42,0.35);
    transform:translateX(100%);
    transition:transform .18s ease;
    display:flex;
    flex-direction:column;
  }
  .detail-overlay.open .detail-panel{
    transform:translateX(0);
  }
  .detail-header{
    padding:12px 16px;
    border-bottom:1px solid #e5e7eb;
    display:flex;
    align-items:center;
    justify-content:space-between;
  }
  .detail-title{
    font-size:14px;
    font-weight:600;
  }
  .detail-body{
    padding:12px 16px 18px;
    overflow-y:auto;
    font-size:12px;
    color:#374151;
  }
  .detail-label{
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.04em;
    color:#9ca3af;
  }
  .detail-value{
    font-size:12px;
    font-weight:500;
    color:#111827;
  }
  .detail-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:8px 16px;
    margin-top:8px;
  }
  .detail-notes{
    margin-top:12px;
    padding-top:8px;
    border-top:1px dashed #e5e7eb;
  }
  .detail-close-btn{
    border-radius:999px;
    border:1px solid rgba(15,23,42,0.25);
    padding:4px 8px;
    font-size:11px;
    cursor:pointer;
    background:#f9fafb;
  }

  /* === TOAST === */
  .toast-pixel{
    position:fixed;
    bottom:1.5rem;
    right:1.5rem;
    z-index:80;
    padding:8px 12px;
    border-radius:10px;
    border:1px solid rgba(15,23,42,0.3);
    background:#ecfdf3;
    box-shadow:0 10px 30px rgba(15,23,42,0.28);
    font-size:11px;
    font-weight:600;
    color:#14532d;
    opacity:0;
    pointer-events:none;
  }
  .toast-error{
    background:#fef2f2;
    color:#7f1d1d;
  }
  .toast-show{
    animation:toastPop .2s ease-out forwards;
  }
  .toast-hide{
    animation:toastHide .18s ease-in forwards;
  }
  @keyframes toastPop{
    from{ opacity:0; transform:translateY(8px); }
    to{ opacity:1; transform:translateY(0); }
  }
  @keyframes toastHide{
    from{ opacity:1; transform:translateY(0); }
    to{ opacity:0; transform:translateY(4px); }
  }
  @media (max-width:768px){
    .pixel-panel{ padding:1.1rem; }
    .toast-pixel{
      left:50%;
      right:auto;
      transform:translateX(-50%);
    }
  }
</style>

<div class="page-wrap">
  <div class="pixel-panel">

    {{-- Header and CTA --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-5">
      <div>
        <div class="pixel-tag">
          <span class="pixel-tag-dot"></span>
          <span>Raw Materials · GenRev Meat Products</span>
        </div>

        <h2 class="pixel-title">
          Raw Materials Inventory
          <span>Production Ready</span>
        </h2>
        <div class="pixel-breadcrumb">
          <span class="pixel-link">Dashboard</span>
          <span>/</span>
          <span>Inventory</span>
          <span>/</span>
          <span>Raw Materials</span>
        </div>
        <p class="pixel-subtitle">Live overview of meat and ingredient stocks used for production, costing, and planning.</p>
      </div>

      <button type="button"
              class="btn btn-primary"
              data-open="modalCreate"
              aria-haspopup="dialog"
              aria-controls="modalCreate"
              title="Add a new material">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
          <path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z"/>
        </svg>
        Add Material
      </button>
    </div>

    {{-- KPI cards --}}
    <div class="pixel-kpi-wrap mb-6">
      <div class="pixel-kpi">
        <div class="pixel-kpi-label">Total Items</div>
        <div class="pixel-kpi-value">{{ number_format($stats['count']) }}</div>
      </div>

      <div class="pixel-kpi">
        <div class="pixel-kpi-label">Inventory Valuation</div>
        <div class="pixel-kpi-value">₱ {{ number_format($stats['valuation'], 2) }}</div>
      </div>

      <div class="pixel-kpi">
        <div class="pixel-kpi-label">Low Stock Materials</div>
        <div class="pixel-kpi-value">{{ number_format($stats['low']) }}</div>
      </div>

      {{-- Predictive usage trend graph --}}
      <div class="pixel-kpi spark-card">
        <div class="pixel-kpi-label">Material Usage Forecast</div>
        <div class="spark-bars">
          @php
            $maxVal    = max($sparkData ?: [1]);
            $totalBars = count($sparkData);
          @endphp
          @foreach($sparkData as $i => $val)
            @php
              $height  = 12 + ($maxVal > 0 ? ($val / $maxVal) * 18 : 0); // 12–30px
              $opacity = 0.35 + (($i + 1) / max($totalBars,1)) * 0.45;   // fade from light to strong
            @endphp
            <div class="spark-bar {{ $i === $totalBars - 1 ? 'spark-bar-strong' : '' }}"
                 style="height:{{ $height }}px;opacity:{{ $opacity }};">
            </div>
          @endforeach
        </div>
        <div class="spark-caption">
          Projected daily usage next 7 days
          ≈ <span>{{ number_format($predictedNext7, 1) }} kg</span>
          vs 30-day baseline
          <span class="{{ $trendUp ? '' : 'spark-worse' }}">{{ number_format($avg30, 1) }} kg</span>
        </div>
      </div>
    </div>

    {{-- Filters --}}
    <form id="filterForm" action="{{ route('materials.index') }}" method="GET" class="mb-4" role="search">
      <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
        <div class="md:col-span-2">
          <label class="mb-1 block" for="searchBox">Search</label>
          <div class="flex gap-2">
            <input id="searchBox" type="text" name="search" value="{{ $search }}" placeholder="Search by material name or SKU" class="input-light" />
            <button type="submit" class="btn btn-ghost">Go</button>
            <a href="{{ route('materials.index') }}" class="btn btn-ghost">Reset</a>
          </div>
        </div>
        <div>
          <label class="mb-1 block">Category</label>
          <select name="category" class="input-light" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach($categoryCatalog as $_c)
              <option value="{{ $_c }}" @selected($cat === $_c)>{{ $_c }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="mb-1 block">Sort</label>
          <select name="sort" class="input-light" onchange="this.form.submit()">
            @foreach($sortOptions as $k => $label)
              <option value="{{ $k }}" @selected($sort === $k)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="mb-1 block">Rows</label>
          <select name="per_page" class="input-light" onchange="this.form.submit()">
            @foreach([25,50,100,200,'all'] as $_pp)
              <option value="{{ $_pp }}" @selected((string)$perPage === (string)$_pp)>{{ is_numeric($_pp) ? $_pp : 'All' }}</option>
            @endforeach
          </select>
        </div>
        <div class="flex items-center gap-2">
          <input id="low_stock" type="checkbox" name="low_stock" value="1" {{ $lowOnly ? 'checked' : '' }} onchange="this.form.submit()"/>
          <label for="low_stock" class="text-[11px] text-gray-700">Show low stock only</label>
        </div>
      </div>

    </form>

    {{-- Table --}}
    <div class="pixel-table-wrap overflow-x-auto">
      <table class="pixel-table text-left">
        <thead>
          <tr>
            <th>Name</th>
            <th class="w-64">Category</th>
            <th class="w-24">Unit</th>
            <th class="w-32 text-right">Unit Price</th>
            <th class="w-32 text-right">Quantity (kg)</th>
            <th class="w-32 text-center">Days of stock</th>
            <th class="w-40 text-right">Line Value</th>
            <th class="w-24 text-center">Used In</th>
            <th class="w-40">Last Updated</th>
            <th class="w-[320px] text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
        @forelse($rows as $m)
          @php
            $category   = $m->category ?? infer_category($m);
            $qty        = (float)($m->quantity_kg ?? 0);
            $price      = (float)($m->unit_price ?? 0);
            $lineVal    = $qty * $price;
            $badgeClass = (str_contains($category,'Spices') || str_contains($category,'Fillers')) ? 'b-amber' : 'b-green';
            $isLow      = !is_null($m->min_stock_kg ?? null) && $qty < (float)$m->min_stock_kg;
            $dotColor   = $isLow ? '#f97316' : '#22c55e';

            // Per-material average daily usage (if provided by controller/model)
            $dailyUsage   = (float)($m->avg_daily_usage_30d ?? 0);
            $daysStock    = $dailyUsage > 0 ? $qty / $dailyUsage : null;
            if (is_null($daysStock)) {
              $daysLabel = '—';
              $daysClass = 'b-gray';
            } else {
              $daysRounded = round($daysStock, 1);
              $daysLabel   = $daysRounded.' d';
              if ($daysRounded < 7) {
                $daysClass = 'b-red';
              } elseif ($daysRounded < 14) {
                $daysClass = 'b-amber';
              } else {
                $daysClass = 'b-green';
              }
            }

            // Data for detail slide-in panel
            $detailPayload = [
              'name'          => $m->material_name,
              'sku'           => $m->sku,
              'category'      => $category,
              'unit'          => $m->unit,
              'unit_price'    => $price,
              'quantity_kg'   => $qty,
              'min_stock_kg'  => $m->min_stock_kg,
              'days_of_stock' => isset($daysRounded) ? $daysRounded : null,
              'is_low'        => $isLow,
              'used_in'       => (int)($m->used_in_products ?? 0),
              'supplier_name' => $m->supplier_name,
              'batch_code'    => $m->batch_code,
              'storage_type'  => $m->storage_type,
              'manufactured_at' => optional($m->manufactured_at)->format('Y-m-d'),
              'received_at'     => optional($m->received_at)->format('Y-m-d'),
              'expires_at'      => optional($m->expires_at)->format('Y-m-d'),
              'notes'           => $m->notes,
              'updated_at'      => optional($m->updated_at)->format('Y-m-d H:i'),
            ];
          @endphp
          @if((!$cat || $cat === $category) && (!$lowOnly || $isLow))
            <tr class="material-row"
                data-material='@json($detailPayload)'>
              <td>
                <div class="font-medium flex items-center gap-2">
                  <span class="status-dot" style="background:{{ $dotColor }};"></span>
                  {{ $m->material_name }}
                </div>
              </td>
              <td>
                <span class="badge {{ $badgeClass }}">{{ $category }}</span>
              </td>
              <td>
                <span class="badge b-gray">{{ $m->unit }}</span>
              </td>
              <td class="text-right">₱ {{ number_format($price, 2) }}</td>
              <td class="text-right">{{ number_format($qty, 3) }}</td>
              <td class="text-center">
                <span class="badge {{ $daysClass }}">{{ $daysLabel }}</span>
              </td>
              <td class="text-right">₱ {{ number_format($lineVal, 2) }}</td>
              <td class="text-center">
                <span class="badge b-gray">{{ (int)($m->used_in_products ?? 0) }}</span>
              </td>
              <td class="text-gray-600">{{ optional($m->updated_at)->format('Y-m-d H:i') }}</td>
              <td>
                <div class="table-actions">
                  <button type="button"
                          class="btn btn-green text-[11px]"
                          data-adjust-url="{{ route('materials.adjust',$m->id) }}"
                          data-name="{{ $m->material_name }}"
                          title="Adjust stock for {{ $m->material_name }}">
                    Adjust stock
                  </button>

                  <form action="{{ route('materials.destroy',$m->id) }}" method="POST" onsubmit="return confirm('Delete this material?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-ghost text-[11px]" title="Delete {{ $m->material_name }}">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          @endif
        @empty
          <tr>
            <td colspan="10" class="py-6 px-4 text-center text-gray-600">No materials found.</td>
          </tr>
        @endforelse
        </tbody>
        <tfoot>
          @php
            $grand = (float)($stats['valuation'] ?? 0);
          @endphp
          <tr>
            <td colspan="6" class="text-right text-gray-700">Total Unit Material Cost</td>
            <td class="text-right font-bold text-red-800" id="grandTotal">₱ {{ number_format($grand, 2) }}</td>
            <td colspan="3"></td>
          </tr>
        </tfoot>
      </table>
    </div>

    @if($isPaginated)
      <div class="mt-4">
        {{ $materials->withQueryString()->links() }}
      </div>
    @endif

    <p class="pixel-footnote">This list serves as the reference for recipe costing, production scheduling, and purchasing decisions.</p>
  </div>
</div>

{{-- TOAST --}}
<div id="toastPixel" class="toast-pixel" role="status" aria-live="polite"></div>

{{-- DETAIL SLIDE-IN PANEL --}}
<div id="materialDetailOverlay" class="detail-overlay" aria-hidden="true">
  <div class="detail-panel" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
    <div class="detail-header">
      <div>
        <div id="detailTitle" class="detail-title">Material details</div>
        <div class="text-xs text-gray-500" id="detailSubtitle"></div>
      </div>
      <button type="button" class="detail-close-btn" data-detail-close>Close</button>
    </div>
    <div class="detail-body">
      <div class="detail-grid">
        <div>
          <div class="detail-label">Material</div>
          <div class="detail-value" id="detailName">—</div>
        </div>
        <div>
          <div class="detail-label">SKU</div>
          <div class="detail-value" id="detailSku">—</div>
        </div>
        <div>
          <div class="detail-label">Category</div>
          <div class="detail-value" id="detailCategory">—</div>
        </div>
        <div>
          <div class="detail-label">Storage</div>
          <div class="detail-value" id="detailStorage">—</div>
        </div>
        <div>
          <div class="detail-label">Unit / Price</div>
          <div class="detail-value" id="detailUnitPrice">—</div>
        </div>
        <div>
          <div class="detail-label">Qty / Min stock</div>
          <div class="detail-value" id="detailQtyMin">—</div>
        </div>
        <div>
          <div class="detail-label">Days of stock</div>
          <div class="detail-value" id="detailDays">—</div>
        </div>
        <div>
          <div class="detail-label">Used in recipes</div>
          <div class="detail-value" id="detailUsedIn">—</div>
        </div>
        <div>
          <div class="detail-label">Supplier</div>
          <div class="detail-value" id="detailSupplier">—</div>
        </div>
        <div>
          <div class="detail-label">Batch code</div>
          <div class="detail-value" id="detailBatch">—</div>
        </div>
        <div>
          <div class="detail-label">Manufactured</div>
          <div class="detail-value" id="detailMfg">—</div>
        </div>
        <div>
          <div class="detail-label">Received</div>
          <div class="detail-value" id="detailReceived">—</div>
        </div>
        <div>
          <div class="detail-label">Expiry</div>
          <div class="detail-value" id="detailExpiry">—</div>
        </div>
        <div>
          <div class="detail-label">Last updated</div>
          <div class="detail-value" id="detailUpdated">—</div>
        </div>
      </div>

      <div class="detail-notes">
        <div class="detail-label mb-1">Notes</div>
        <div class="detail-value whitespace-pre-line" id="detailNotes">—</div>
      </div>
    </div>
  </div>
</div>

{{-- CREATE MODAL --}}
<dialog id="modalCreate" class="modal" aria-label="Add Material">
  <form method="POST" action="{{ route('materials.store') }}" class="modal-box w-full max-w-2xl">
    @csrf
    <h3 class="modal-title">Add Raw Material</h3>

    {{-- Validation errors --}}
    @if($errors->any())
      <div class="mb-3 text-xs rounded-md border border-red-300 bg-red-50 px-3 py-2 text-red-700">
        <ul class="list-disc pl-4 space-y-0.5">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      {{-- Identity --}}
      <div>
        <label class="mb-1 block">Material name</label>
        <input
          name="material_name"
          required
          class="input-light"
          placeholder="e.g., Pork Lean, Beef Trimmings"
          value="{{ old('material_name') }}"
        />
      </div>

      <div>
        <label class="mb-1 block">Category</label>
        <select name="category" class="input-light">
          <option value="">Select category</option>
          @foreach($categoryCatalog as $_c)
            <option value="{{ $_c }}" @selected(old('category') === $_c)>{{ $_c }}</option>
          @endforeach
        </select>
      </div>

      {{-- Base unit + pricing --}}
      <div>
        <label class="mb-1 block">Unit</label>
        <select name="unit" class="input-light" required>
          @foreach($unitOptions as $v => $label)
            <option value="{{ $v }}" @selected(old('unit','kg') === $v)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="mb-1 block">Unit Price (₱)</label>
        <input
          name="unit_price"
          type="number"
          min="0"
          step="0.01"
          class="input-light"
          value="{{ old('unit_price', 0) }}"
        />
      </div>

      {{-- Stock levels --}}
      <div>
        <label class="mb-1 block">Quantity (kg)</label>
        <input
          name="quantity_kg"
          type="number"
          min="0"
          step="0.001"
          class="input-light"
          value="{{ old('quantity_kg', 0) }}"
        />
      </div>

      <div>
        <label class="mb-1 block">Min Stock (kg)</label>
        <input
          name="min_stock_kg"
          type="number"
          min="0"
          step="0.001"
          class="input-light"
          value="{{ old('min_stock_kg') }}"
        />
      </div>

      {{-- SKU --}}
      <div class="md:col-span-2">
        <label class="mb-1 block">SKU (optional)</label>
        <input
          name="sku"
          class="input-light"
          placeholder="e.g., MT-PORK-LEAN"
          value="{{ old('sku') }}"
        />
      </div>

      {{-- Supplier + Batch --}}
      <div>
        <label class="mb-1 block">Supplier name (optional)</label>
        <input
          name="supplier_name"
          class="input-light"
          placeholder="e.g., ABC Meats Trading"
          value="{{ old('supplier_name') }}"
        />
      </div>

      <div>
        <label class="mb-1 block">Batch code (optional)</label>
        <input
          name="batch_code"
          class="input-light"
          placeholder="Leave blank to auto-generate"
          value="{{ old('batch_code') }}"
        />
      </div>

      {{-- Storage + dates --}}
      <div>
        <label class="mb-1 block">Storage type</label>
        <select name="storage_type" class="input-light">
          <option value="">Select storage</option>
          <option value="chiller" @selected(old('storage_type') === 'chiller')>Chiller</option>
          <option value="freezer" @selected(old('storage_type') === 'freezer')>Freezer</option>
          <option value="dry" @selected(old('storage_type') === 'dry')>Dry storage</option>
          <option value="ambient" @selected(old('storage_type') === 'ambient')>Ambient</option>
        </select>
      </div>

      <div>
        <label class="mb-1 block">Manufactured date</label>
        <input
          type="date"
          name="manufactured_at"
          class="input-light"
          value="{{ old('manufactured_at') }}"
        />
      </div>

      <div>
        <label class="mb-1 block">Received date</label>
        <input
          type="date"
          name="received_at"
          class="input-light"
          value="{{ old('received_at') }}"
        />
      </div>

      <div>
        <label class="mb-1 block">Expiry date</label>
        <input
          type="date"
          name="expires_at"
          class="input-light"
          value="{{ old('expires_at') }}"
        />
      </div>

      {{-- Notes --}}
      <div class="md:col-span-2">
        <label class="mb-1 block">Notes (optional)</label>
        <textarea
          name="notes"
          rows="2"
          class="input-light"
          placeholder="Specs, supplier notes, packaging details, etc."
        >{{ old('notes') }}</textarea>
      </div>
    </div>

    <div class="flex justify-end gap-2 mt-6">
      <button type="button" class="btn btn-ghost" data-close>Cancel</button>
      <button type="submit" class="btn btn-primary">Save</button>
    </div>
  </form>
</dialog>

{{-- ADJUST STOCK MODAL --}}
<dialog id="modalAdjust" class="modal" aria-label="Adjust Stock">
  <form id="adjustForm" method="POST" class="modal-box w-full max-w-md">
    @csrf
    <h3 class="modal-title">Adjust Stock</h3>
    <p class="text-[12px] text-gray-700 mb-3">Material: <span id="adjustName" class="font-semibold"></span></p>
    <div class="grid grid-cols-1 gap-4">
      <div>
        <label class="mb-1 block">Delta (kg)</label>
        <input name="delta_kg" type="number" step="0.001" class="input-light" placeholder="e.g., 5 or -2.5" required />
      </div>
      <div>
        <label class="mb-1 block">Reason (optional)</label>
        <input name="reason" class="input-light" placeholder="Receiving PO-123, correction, spoilage, etc." />
      </div>
    </div>
    <div class="flex justify-end gap-2 mt-6">
      <button type="button" class="btn btn-ghost" data-close>Cancel</button>
      <button type="submit" class="btn btn-green">Apply</button>
    </div>
  </form>
</dialog>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const $  = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));
  const byId = (id) => document.getElementById(id);

  const toastEl = byId('toastPixel');
  let toastTimeout;

  function showToast(message, type = 'success') {
    if (!toastEl) return;
    toastEl.textContent = message || '';
    toastEl.classList.remove('toast-hide','toast-error','toast-show');
    if (type === 'error') {
      toastEl.classList.add('toast-error');
    }
    toastEl.classList.add('toast-show');
    toastEl.style.pointerEvents = 'auto';

    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
      toastEl.classList.add('toast-hide');
      toastEl.classList.remove('toast-show');
      toastEl.style.pointerEvents = 'none';
    }, 1300);
  }

  // Open any dialog by [data-open]
  document.addEventListener('click', (e) => {
    const openId = e.target?.getAttribute('data-open');
    if (openId) {
      const dlg = byId(openId);
      if (dlg?.showModal) dlg.showModal(); else dlg?.setAttribute('open','open');
    }
  });

  // Close dialog via [data-close]
  document.addEventListener('click', (e) => {
    if (e.target?.hasAttribute('data-close')) {
      const dlg = e.target.closest('dialog');
      try { dlg?.close(); } catch { dlg?.removeAttribute('open'); }
    }
  }, true);

  // Adjust stock dialog
  const adjustModal = byId('modalAdjust');
  const adjustForm  = byId('adjustForm');
  const adjustName  = byId('adjustName');

  $$('.btn[data-adjust-url]').forEach(btn => {
    btn.addEventListener('click', (ev) => {
      ev.stopPropagation(); // prevent row detail opening
      const url  = btn.dataset.adjustUrl;
      const name = btn.dataset.name || '—';
      adjustForm.setAttribute('action', url);
      adjustName.textContent = name;
      if (adjustModal?.showModal) adjustModal.showModal(); else adjustModal?.setAttribute('open','open');
    });
  });

  // Adjust submit via fetch, fallback to normal submit if fetch fails
  adjustForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const action = adjustForm.getAttribute('action');
    const fd = new FormData(adjustForm);

    try {
      const res = await fetch(action, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: fd
      });
      const json = await res.json();
      if (!res.ok || !json.ok) throw new Error(json.message || 'Failed');

      try { adjustModal?.close(); } catch { adjustModal?.removeAttribute('open'); }

      showToast(json.message || 'Stock adjusted.', 'success');
      setTimeout(() => {
        location.reload();
      }, 900);
    } catch (err) {
      console.warn(err);
      showToast('Adjustment failed, submitting normally.', 'error');
      setTimeout(() => {
        adjustForm.submit();
      }, 500);
    }
  });

  // === Detail slide-in panel logic ===
  const detailOverlay  = byId('materialDetailOverlay');
  const detailName     = byId('detailName');
  const detailSku      = byId('detailSku');
  const detailCategory = byId('detailCategory');
  const detailStorage  = byId('detailStorage');
  const detailUnitPrice= byId('detailUnitPrice');
  const detailQtyMin   = byId('detailQtyMin');
  const detailDays     = byId('detailDays');
  const detailUsedIn   = byId('detailUsedIn');
  const detailSupplier = byId('detailSupplier');
  const detailBatch    = byId('detailBatch');
  const detailMfg      = byId('detailMfg');
  const detailReceived = byId('detailReceived');
  const detailExpiry   = byId('detailExpiry');
  const detailUpdated  = byId('detailUpdated');
  const detailNotes    = byId('detailNotes');
  const detailSubtitle = byId('detailSubtitle');

  function openDetail(data) {
    if (!detailOverlay) return;
    detailName.textContent     = data.name || '—';
    detailSku.textContent      = data.sku || '—';
    detailCategory.textContent = data.category || '—';
    detailStorage.textContent  = data.storage_type || '—';

    const unitText  = (data.unit || '—');
    const priceText = typeof data.unit_price === 'number'
      ? '₱ ' + (Math.round(data.unit_price * 100)/100).toFixed(2)
      : '₱ 0.00';
    detailUnitPrice.textContent = `${unitText} · ${priceText}`;

    const qtyText  = typeof data.quantity_kg === 'number'
      ? (Math.round(data.quantity_kg * 1000)/1000).toFixed(3) + ' kg'
      : '0.000 kg';
    const minText  = data.min_stock_kg != null
      ? (Math.round(data.min_stock_kg * 1000)/1000).toFixed(3) + ' kg'
      : '—';
    detailQtyMin.textContent = `${qtyText} / Min ${minText}`;

    if (data.days_of_stock != null) {
      detailDays.textContent = data.days_of_stock.toFixed(1) + ' days';
    } else {
      detailDays.textContent = '—';
    }

    detailUsedIn.textContent   = (data.used_in ?? 0) + ' product(s)';
    detailSupplier.textContent = data.supplier_name || '—';
    detailBatch.textContent    = data.batch_code || '—';
    detailMfg.textContent      = data.manufactured_at || '—';
    detailReceived.textContent = data.received_at || '—';
    detailExpiry.textContent   = data.expires_at || '—';
    detailUpdated.textContent  = data.updated_at || '—';
    detailNotes.textContent    = data.notes || '—';

    detailSubtitle.textContent = data.is_low ? 'Status: Low stock' : 'Status: OK';

    detailOverlay.classList.add('open');
    detailOverlay.setAttribute('aria-hidden','false');
  }

  function closeDetail() {
    if (!detailOverlay) return;
    detailOverlay.classList.remove('open');
    detailOverlay.setAttribute('aria-hidden','true');
  }

  // row click to open detail
  $$('.material-row').forEach(tr => {
    tr.addEventListener('click', (e) => {
      // ignore clicks on buttons/links/forms
      if (e.target.closest('button, a, input, select, textarea, form')) return;
      const raw = tr.getAttribute('data-material');
      if (!raw) return;
      let data;
      try { data = JSON.parse(raw); } catch { data = null; }
      if (!data) return;
      openDetail(data);
    });
  });

  detailOverlay?.addEventListener('click', (e) => {
    if (e.target === detailOverlay) {
      closeDetail();
    }
  });

  $$('[data-detail-close]').forEach(btn => {
    btn.addEventListener('click', closeDetail);
  });

  // Auto-open Add Material modal when there are validation errors
  @if($errors->any())
    const createModal = byId('modalCreate');
    if (createModal) {
      if (createModal.showModal) {
        createModal.showModal();
      } else {
        createModal.setAttribute('open','open');
      }
    }
  @endif
});
</script>
@endpush
