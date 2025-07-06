<?php
// config.php
$host = 'localhost';
$db   = 'inventory_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<?php
// fetch_inventory.php
require 'config.php';

header('Content-Type: application/json');

try {
    // Fetch products
    $stmtProducts = $pdo->query("SELECT * FROM products ORDER BY production_date DESC");
    $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);

    // Fetch materials
    $stmtMaterials = $pdo->query("SELECT * FROM materials ORDER BY material_name ASC");
    $materials = $stmtMaterials->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'products' => $products,
        'materials' => $materials
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

{{-- resources/views/inventory/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
 @include('partials.alerts')
    <h1>Add New Inventory Item</h1>

    <form action="{{ route('inventory.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Batch</label>
            <input type="number" name="batch" class="form-control" value="{{ old('batch') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="{{ old('date') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Quantity</label>
            <input type="number" step="0.01" name="quantity" class="form-control" value="{{ old('quantity') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Status</label>
            <input type="text" name="status" class="form-control" value="{{ old('status') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
                <option value="finished" {{ old('type') == 'finished' ? 'selected' : '' }}>Finished</option>
                <option value="raw" {{ old('type') == 'raw' ? 'selected' : '' }}>Raw Material</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Unit</label>
            <input type="text" name="unit" class="form-control" value="{{ old('unit') }}">
        </div>

        <button type="submit" class="btn btn-primary">Add Item</button>
        <a href="{{ route('inventory.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
