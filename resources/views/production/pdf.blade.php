<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Production Batch PDF</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h2 { margin: 0 0 10px; }
    table { width:100%; border-collapse: collapse; }
    td, th { border:1px solid #ddd; padding:6px; text-align:left; }
  </style>
</head>
<body>
  <h2>Production Batch</h2>
  <table>
    <tr><th>Product</th><td>{{ $batch->product->product_name ?? '—' }}</td></tr>
    <tr><th>Batch #</th><td>{{ $batch->batch_number }}</td></tr>
    <tr><th>Quantity</th><td>{{ number_format($batch->quantity, 3) }} kg</td></tr>
    <tr><th>Unit Cost</th><td>₱{{ number_format($batch->unit_cost, 2) }}</td></tr>
    <tr><th>Price/Pack</th><td>₱{{ number_format($batch->unit_price_pack ?? 0, 2) }}</td></tr>
    <tr><th>Price/Bag</th><td>₱{{ number_format($batch->unit_price_bag ?? 0, 2) }}</td></tr>
    <tr><th>Production Date</th><td>{{ \Carbon\Carbon::parse($batch->production_date)->toFormattedDateString() }}</td></tr>
    <tr><th>Expiry</th><td>{{ $batch->expiration_date ? \Carbon\Carbon::parse($batch->expiration_date)->toFormattedDateString() : '—' }}</td></tr>
    <tr><th>Notes</th><td>{{ $batch->notes ?? '—' }}</td></tr>
  </table>
</body>
</html>
