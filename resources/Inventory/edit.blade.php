@extends('layouts.app')

@section('content')
<div class="container">
 @include('partials.alerts')
    <h1>Edit Inventory Item</h1>

    <form action="{{ route('inventory.update', $inventory) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $inventory->name) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Batch</label>
            <input type="number" name="batch" class="form-control" value="{{ old('batch', $inventory->batch) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="{{ old('date', $inventory->date) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Quantity</label>
            <input type="number" step="0.01" name="quantity" class="form-control" value="{{ old('quantity', $inventory->quantity) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Status</label>
            <input type="text" name="status" class="form-control" value="{{ old('status', $inventory->status) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
                <option value="finished" {{ old('type', $inventory->type) == 'finished' ? 'selected' : '' }}>Finished</option>
                <option value="raw" {{ old('type', $inventory->type) == 'raw' ? 'selected' : '' }}>Raw Material</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Unit</label>
            <input type="text" name="unit" class="form-control" value="{{ old('unit', $inventory->unit) }}">
        </div>

        <button type="submit" class="btn btn-success">Update</button>
        <a href="{{ route('inventory.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
