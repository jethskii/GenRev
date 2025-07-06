@extends('layouts.master')

@section('content')
<div class="container">
    <h2 class="text-white mb-4">Production</h2>

    <div class="card bg-dark text-white">
        <div class="card-body">
            <table class="table table-dark table-striped">
                <thead>
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
                            <td>{{ $production->production_date }}</td>
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
        </div>
    </div>
</div>
@endsection
