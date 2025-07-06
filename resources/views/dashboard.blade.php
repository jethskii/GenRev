@extends('layout.mainlayout')

@section('content')
<div class="h-full grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- Top Metrics --}}
    <div class="grid grid-cols-2 gap-4">
        @php
            $metrics = [
                ['label' => 'Total Products', 'value' => $totalProducts, 'note' => 'Based on monthly inventory'],
                ['label' => 'Total Materials (kg)', 'value' => number_format($totalMaterialsWeight, 2), 'note' => 'Based on raw materials weight'],
                ['label' => 'Total Revenue', 'value' => '₱' . number_format($totalRevenue, 2), 'note' => 'From weekly product sales'],
                ['label' => 'Sales Transactions', 'value' => $totalSales, 'note' => 'Number of weekly transactions'],
            ];
        @endphp

        @foreach ($metrics as $metric)
        <div class="bg-dark-bg text-white border border-dark-line shadow-md p-4 rounded-lg">
            <h2 class="text-xs uppercase font-semibold text-gray-300">{{ $metric['label'] }}</h2>
            <p class="text-2xl font-bold mt-1">{{ $metric['value'] }}</p>
            <p class="text-[10px] mt-1 text-gray-400">Based on <strong>{{ $metric['note'] }}</strong></p>
        </div>
        @endforeach
    </div>


    {{-- Recent Sales Table --}}
    <div class="bg-dark-bg text-white border border-dark-line shadow-md p-4 rounded-lg overflow-auto">
        <h2 class="text-sm font-semibold mb-2">Recent Sales</h2>
        <p class="text-xs text-gray-400 mb-2">Latest from <strong>weekly product sales</strong></p>
        <table class="w-full text-left border-collapse text-xs">
            <thead class="bg-sidebar text-white uppercase">
                <tr>
                    <th class="py-2 px-3 border-b border-dark-line">Product</th>
                    <th class="py-2 px-3 border-b border-dark-line">Qty</th>
                    <th class="py-2 px-3 border-b border-dark-line">Price</th>
                    <th class="py-2 px-3 border-b border-dark-line">Date</th>
                </tr>
            </thead>
            <tbody class="text-gray-100">
                @forelse ($recentSales as $sale)
                    <tr class="border-t border-dark-line hover:bg-sidebar-hover">
                        <td class="py-2 px-3">{{ $sale->product_name }}</td>
                        <td class="py-2 px-3">{{ $sale->quantity }}</td>
                        <td class="py-2 px-3">₱{{ number_format($sale->price, 2) }}</td>
                        <td class="py-2 px-3">{{ \Carbon\Carbon::parse($sale->date)->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-2 text-center text-gray-400">No sales found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Weekly Production Chart --}}
    <div class="bg-dark-bg text-white border border-dark-line shadow-md p-4 rounded-lg">
        <h2 class="text-sm font-semibold mb-2">Weekly Production</h2>
        <canvas id="productionChart" height="140"></canvas>
    </div>

    {{-- Weekly Sales Chart --}}
    <div class="bg-dark-bg text-white border border-dark-line shadow-md p-4 rounded-lg">
        <h2 class="text-sm font-semibold mb-2">Weekly Sales</h2>
        <canvas id="salesChart" height="140"></canvas>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const productionChart = new Chart(
        document.getElementById('productionChart').getContext('2d'),
        {
            type: 'bar',
            data: {
                labels: @json($weeklyProduction->pluck('product_name')),
                datasets: [{
                    label: 'Units Produced',
                    data: @json($weeklyProduction->pluck('weekly_total')),
                    backgroundColor: '#556b2f'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: { color: '#fff' }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#ddd' },
                        grid: { color: '#2a3527' }
                    },
                    y: {
                        ticks: { color: '#ddd' },
                        grid: { color: '#2a3527' }
                    }
                }
            }
        }
    );

    const salesChart = new Chart(
        document.getElementById('salesChart').getContext('2d'),
        {
            type: 'line',
            data: {
                labels: @json($weeklySales->pluck('day')->map(fn($d) => \Carbon\Carbon::parse($d)->format('D'))),
                datasets: [{
                    label: 'Revenue (₱)',
                    data: @json($weeklySales->pluck('total')),
                    fill: true,
                    backgroundColor: 'rgba(85, 107, 47, 0.2)',
                    borderColor: '#556b2f',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: { color: '#fff' }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#ddd' },
                        grid: { color: '#2a3527' }
                    },
                    y: {
                        ticks: { color: '#ddd' },
                        grid: { color: '#2a3527' }
                    }
                }
            }
        }
    );
</script>
@endsection
