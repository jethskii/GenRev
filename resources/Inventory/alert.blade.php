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

{{-- resources/views/partials/alerts.blade.php --}}
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
