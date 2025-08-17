@extends('layouts.master')

@section('content')
<div class="container">
    <h2 class="text-white mb-4">Production Overview</h2>

    <div class="card bg-dark text-white shadow">
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($productions->count())
                <table class="table table-dark table-hover table-bordered">
                    <thead class="bg-secondary text-light">
                        <tr>
                            <th>Product</th>
                            <th>Quantity Produced</th>
                            <th>Production Date</th>
                            <th>Demand Forecast</th>
                            <th>Inventory After</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($productions as $production)
                            <tr>
                                <td>{{ $production->product_name }}</td>
                                <td>{{ $production->quantity }}</td>
                                <td>{{ \Carbon\Carbon::parse($production->production_date)->format('M d, Y') }}</td>
                                <td>{{ $production->demand_forecast }}</td>
                                <td>{{ $production->inventory_after }}</td>
                                <td>
                                    @if ($production->quantity < $production->demand_forecast)
                                        <span class="badge bg-danger">Low</span>
                                    @elseif ($production->quantity == $production->demand_forecast)
                                        <span class="badge bg-success">Enough</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Overproduced</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center text-muted mt-4">No production records found.</div>
            @endif
        </div>
    </div>
</div>
@endsection
