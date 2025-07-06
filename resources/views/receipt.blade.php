<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - {{ $sale->invoice_number ?? ('INV-' . $sale->id) }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-white text-gray-800 px-6 py-10 text-sm">
    <div class="max-w-2xl mx-auto border border-gray-300 shadow-lg rounded-lg p-6">

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-xl font-bold">GenRev Meat Products Inc.</h1>
                <p class="text-xs text-gray-500">123 Main Street, City, Philippines</p>
                <p class="text-xs text-gray-500">Phone: (02) 1234-5678</p>
            </div>
            <div class="text-right">
                <h2 class="text-lg font-semibold">SALES RECEIPT</h2>
                <p class="text-sm">Invoice #: <strong>{{ $sale->invoice_number ?? ('INV-' . $sale->id) }}</strong></p>
                <p class="text-xs text-gray-500">Date: {{ \Carbon\Carbon::parse($sale->date)->format('F d, Y') }}</p>
            </div>
        </div>

        <!-- Sale Details -->
        <div class="border-t border-b py-4 mb-4">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr>
                        <th class="py-2">Product</th>
                        <th class="py-2 text-right">Qty</th>
                        <th class="py-2 text-right">Price</th>
                        <th class="py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="py-2 font-medium">{{ $sale->product_name }}</td>
                        <td class="py-2 text-right">{{ $sale->quantity }}</td>
                        <td class="py-2 text-right">₱{{ number_format($sale->price, 2) }}</td>
                        <td class="py-2 text-right font-semibold">₱{{ number_format($sale->quantity * $sale->price, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Status -->
        <div class="flex justify-between items-center mb-4">
            <p class="text-sm">Payment Status:</p>
            <span class="px-3 py-1 rounded-full text-xs font-semibold
                {{ $sale->status === 'Paid' ? 'bg-green-100 text-green-800' : ($sale->status === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                {{ $sale->status }}
            </span>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-xs text-gray-500">
            <p>Thank you for your purchase!</p>
            <p class="italic">This receipt was generated electronically.</p>
        </div>
    </div>

    <!-- Buttons -->
    <div class="no-print mt-6 text-center">
        <button onclick="window.print()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow mr-4">
            🖨️ Print Receipt
        </button>
        <a href="{{ url()->previous() }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded shadow">
            🔙 Back
        </a>
    </div>
</body>
</html>
