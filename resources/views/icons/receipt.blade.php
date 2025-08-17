{{-- resources/views/sales/receipt.blade.php --}}
@php
    /** @var \App\Models\Sale $sale */
    $company = [
        'name' => 'GenRev Meat Products Inc.',
        'address' => '123 Processing Ave, Quezon City, PH',
        'email' => 'support@gmp.example',
        'phone' => '+63 900 000 0000',
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt — {{ $sale->invoice_number }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        :root{
            --bg:#0f1420;
            --card:#121a2a;
            --muted:#b7c2d6;
            --text:#ebf0ff;
            --accent:#37d67a;
            --danger:#ff6b6b;
            --warn:#ffd166;
            --border:#22314d;
        }
        *{box-sizing:border-box}
        html,body{margin:0;background:var(--bg);color:var(--text);font:400 14px/1.5 system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial}
        a{color:var(--accent);text-decoration:none}
        .container{max-width:900px;margin:32px auto;padding:0 16px}
        .card{background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));border:1px solid var(--border);border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.35);overflow:hidden}
        .head{display:flex;gap:16px;align-items:center;padding:20px;border-bottom:1px solid var(--border);background:rgba(255,255,255,0.02)}
        .logo{width:40px;height:40px;display:grid;place-items:center;border-radius:10px;background:rgba(55,214,122,.15);border:1px solid rgba(55,214,122,.35)}
        .title{font-size:18px;margin:0}
        .muted{color:var(--muted)}
        .grid{display:grid;gap:14px;padding:20px}
        .grid-2{grid-template-columns:1fr 1fr}
        .sec{background:rgba(255,255,255,0.02);border:1px dashed var(--border);border-radius:12px;padding:14px}
        .sec h4{margin:0 0 8px 0;font-size:13px;letter-spacing:.4px;text-transform:uppercase;color:var(--muted)}
        .kv{display:grid;grid-template-columns:160px 1fr;gap:8px}
        .kv div{padding:3px 0}
        .table{width:100%;border-collapse:collapse;margin-top:6px;border:1px solid var(--border);border-radius:12px;overflow:hidden}
        .table th,.table td{padding:12px 14px;text-align:left;border-bottom:1px solid var(--border)}
        .table thead th{background:rgba(255,255,255,0.04);font-weight:600}
        .table tfoot td{font-weight:700}
        .pill{display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;border:1px solid var(--border)}
        .pill.paid{background:rgba(55,214,122,.12);border-color:rgba(55,214,122,.5);color:#b8ffd7}
        .pill.pending{background:rgba(255,209,102,.12);border-color:rgba(255,209,102,.5);color:#ffe9b0}
        .pill.cancelled{background:rgba(255,107,107,.12);border-color:rgba(255,107,107,.5);color:#ffc6c6}
        .pill.completed{background:rgba(93,173,255,.12);border-color:rgba(93,173,255,.5);color:#cfe3ff}
        .actions{display:flex;gap:10px;flex-wrap:wrap;padding:16px;border-top:1px solid var(--border);background:rgba(255,255,255,0.02)}
        .btn{padding:10px 14px;border-radius:12px;border:1px solid var(--border);background:rgba(255,255,255,0.02);color:var(--text);cursor:pointer;display:flex;align-items:center;gap:6px}
        .btn.primary{border-color:rgba(55,214,122,.5)}
        .btn:hover{filter:brightness(1.1)}
        .footer{padding:14px 20px;color:var(--muted);font-size:12px;text-align:center}
        @media print{
            .actions{display:none!important}
            body{background:#fff;color:#000}
            .card{box-shadow:none;border:none}
            a{color:inherit;text-decoration:none}
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">

        {{-- Header --}}
        <div class="head">
            <div class="logo">
                {{-- Receipt Icon --}}
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M5 3h10l4 4v14H5V3z" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M15 3v5h5" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M8 12h8M8 16h8" stroke="currentColor" stroke-width="1.6"/>
                </svg>
            </div>
            <div>
                <h1 class="title">Receipt</h1>
                <div class="muted">{{ $company['name'] }}</div>
            </div>
            <div style="margin-left:auto;text-align:right">
                <div class="muted">Invoice #</div>
                <div style="font-weight:700">{{ $sale->invoice_number }}</div>
            </div>
        </div>

        {{-- Meta Info --}}
        <div class="grid grid-2">
            <div class="sec">
                <h4>Seller</h4>
                <div class="kv">
                    <div class="muted">Company</div><div>{{ $company['name'] }}</div>
                    <div class="muted">Address</div><div>{{ $company['address'] }}</div>
                    <div class="muted">Email</div><div>{{ $company['email'] }}</div>
                    <div class="muted">Phone</div><div>{{ $company['phone'] }}</div>
                </div>
            </div>
            <div class="sec">
                <h4>Details</h4>
                <div class="kv">
                    <div class="muted">Date</div>
                    <div>{{ optional($sale->date)->format('Y-m-d H:i') }}</div>
                    <div class="muted">Status</div>
                    <div>
                        @php $s = strtolower($sale->status); @endphp
                        <span class="pill {{ $s }}">{{ $sale->status }}</span>
                    </div>
                    <div class="muted">Batch</div>
                    <div>{{ optional($sale->production)->batch_number ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="grid">
            <div class="sec" style="padding:0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $sale->product ?? optional($sale->productRef)->name }}</td>
                            <td>{{ $sale->quantity }}</td>
                            <td>₱ {{ number_format($sale->price, 2) }}</td>
                            <td>₱ {{ number_format($sale->total ?: $sale->quantity * $sale->price, 2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align:right">Grand Total</td>
                            <td>₱ {{ number_format($sale->total ?: $sale->quantity * $sale->price, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Actions --}}
        <div class="actions">
            <a class="btn" href="{{ url()->previous() }}">
                {{-- Back Arrow Icon --}}
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Back
            </a>
            <a class="btn primary" href="{{ route('sales.download', $sale) }}">
                {{-- Download Icon --}}
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 5v14m0 0l6-6m-6 6l-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Download PDF
            </a>
            <button class="btn" onclick="window.print()">
                {{-- Printer Icon --}}
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M6 9V4h12v5M6 18H4a2 2 0 01-2-2v-4a2 2 0 012-2h16a2 2 0 012 2v4a2 2 0 01-2 2h-2v4H6v-4z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Print
            </button>
        </div>

        <div class="footer">
            Generated on {{ now()->format('Y-m-d H:i') }} • Thank you for your business.
        </div>
    </div>
</div>
</body>
</html>
