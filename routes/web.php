<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BatchAllocationController;
use App\Http\Controllers\NotificationController;

/* --------------------------------------------------------------------------
 | Global route param patterns (avoid accidental collisions)
 * -------------------------------------------------------------------------*/
Route::pattern('id', '[0-9]+');
Route::pattern('product', '[0-9]+');
Route::pattern('production', '[0-9]+');
Route::pattern('sale', '[0-9]+');
Route::pattern('allocation', '[0-9]+');
Route::pattern('item', '[0-9]+');
Route::pattern('line', '[0-9]+');

/* Landing */
Route::redirect('/', '/dashboard')->name('home');

/* --------------------------------------------------------------------------
 | Guest (Auth)
 * -------------------------------------------------------------------------*/
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login',   [AuthController::class, 'login'])->name('login.submit');

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register',[AuthController::class, 'register'])->name('register.submit');
});

/* --------------------------------------------------------------------------
 | Authenticated
 * -------------------------------------------------------------------------*/
Route::middleware('auth')->group(function () {

    /* ===== Dashboard ===== */
    Route::get('/dashboard',      [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

    /* ===== Products ===== */
    // Resource gives: products.index/create/store/show/edit/update/destroy
    Route::resource('products', ProductController::class);

    // Inline quick add (header)
    Route::post('products/quick-store', [ProductController::class, 'quickStore'])
        ->name('products.quick-store');

    // Image-only update
    Route::post('products/{product}/image', [ProductController::class, 'updateImage'])
        ->name('products.image.update');

    // Optional: archive endpoint (if implemented in your controller)
    Route::post('products/{id}/archive', [ProductController::class, 'archive'])
        ->name('products.archive');

    /* ----- Product ↔ Materials (Recipes / BOM) ----- */
    Route::prefix('products/{product}')->group(function () {
        Route::get('/materials',           [ProductController::class, 'materialsIndex'])->name('products.materials.index');
        Route::get('/materials/defaults',  [ProductController::class, 'materialsDefaults'])->name('products.materials.defaults');
        Route::post('/recipe',             [ProductController::class, 'recipeStore'])->name('products.recipe.store');
        Route::delete('/recipe/{line}',    [ProductController::class, 'recipeDestroy'])->name('products.recipe.destroy');
    });

    /* ===== Production / Batches ===== */
    Route::prefix('production')->name('production.')->controller(ProductionController::class)->group(function () {
        // Main list + filter
        Route::get('/',        'index')->name('index');
        Route::get('/filter',  'filter')->name('filter');

        // Lightweight info + APIs
        Route::get('/info/{name}',              'getProductInfo')->name('info'); // by product_name (string)
        Route::get('/api/by-product/{product}', 'apiByProduct')->name('api.byProduct');
        Route::get('/{product}/batches',        'apiByProduct')->name('batches');

        // Orders (per-product view)
        Route::get('/orders/{id}', 'showOrders')->name('orders');

        // Create production order
        Route::post('/orders',        'storeOrder')->name('orders.store');
        Route::post('/orders/legacy', 'storeOrder')->name('storeOrder'); // legacy alias

        // Dashboard Add Production
        Route::post('/', 'store')->name('store');

        // Edit/Update/Delete single batch
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::put('/{id}',      'update')->name('update');
        Route::delete('/{production}', 'destroy')->name('destroy');

        // (Optional, UI currently hides this) Delete latest batch for a product
        Route::delete('/batch/latest/{product}', 'destroyLatest')->name('batch.destroyLatest');

        // Quick Add payload (used by product card button)
        Route::get('/quick-add/{product}', 'quickAddPayload')->name('quickAdd');

        // Product detail inside Production module
        Route::get('/{id}', 'show')->name('show');
    });

    /* >>> Alias so route('production') resolves to Production index <<< */
    Route::get('/production-alias', fn () => redirect()->route('production.index'))->name('production');

    /* ===== Sales ===== */
    Route::resource('sales', SalesController::class)->except(['create','show']);

    // Availability check (used by Add Sale & Edit page)
    Route::post('/inventory/available', [SalesController::class, 'available'])->name('sales.available');

    // One-click Quick Add sale (AJAX)
    Route::post('/sales/quick-store', [SalesController::class, 'quickStore'])->name('sales.quickStore');

    // Receipt & PDF
    Route::get('/sales/{sale}/receipt',  [SalesController::class, 'receipt'])->name('sales.receipt');
    Route::get('/sales/{sale}/download', [SalesController::class, 'download'])->name('sales.download');

    // Handy alias
    Route::get('/sales-alias', fn () => redirect()->route('sales.index'))->name('sales');

    /* ===== Allocations ===== */
    Route::prefix('allocations')->name('allocations.')->group(function () {
        Route::patch('/{allocation}/approve',    [BatchAllocationController::class, 'approve'])->name('approve');
        Route::patch('/{allocation}/release',    [BatchAllocationController::class, 'release'])->name('release');
        Route::patch('/{allocation}/reallocate', [BatchAllocationController::class, 'reallocate'])->name('reallocate');
        Route::delete('/{allocation}',           [BatchAllocationController::class, 'destroy'])->name('destroy');
        Route::get('/by-item/{item}',            [BatchAllocationController::class, 'byItem'])->name('byItem');
    });

        /* ===== Materials ===== */
    Route::prefix('materials')->group(function () {
        Route::get('/create',     [MaterialController::class, 'create'])->name('materials.create');
        Route::get('/',           [MaterialController::class, 'index'])->name('materials.index');
        Route::post('/',          [MaterialController::class, 'store'])->name('materials.store');
        Route::post('/store',     [MaterialController::class, 'store'])->name('materials.store.alias');
        Route::get('{id}/edit',   [MaterialController::class, 'edit'])->name('materials.edit');
        Route::put('{id}',        [MaterialController::class, 'update'])->name('materials.update');
        Route::delete('{id}',     [MaterialController::class, 'destroy'])->name('materials.destroy');
    });

    /* 👇 bring back the alias so route('materials') works */
    Route::get('/materials-alias', fn () => redirect()->route('materials.index'))->name('materials');

    /* ===== Inventory ===== */
    Route::get('/inventory',           [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory',          [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{id}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
    Route::put('/inventory/{id}',      [InventoryController::class, 'update'])->name('inventory.update');
    Route::get('/inventory-alias', fn () => redirect()->route('inventory.index'))->name('inventory');

    /* ===== Employee ===== */
    Route::get('/employee',       [EmployeeController::class, 'index'])->name('employee.index');
    Route::post('/employee',      [EmployeeController::class, 'store'])->name('employee.store');
    Route::get('/employee-alias', fn () => redirect()->route('employee.index'))->name('employee');

    /* ===== Settings ===== */
    Route::get('/settings',                 [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings',                [SettingsController::class, 'store'])->name('settings.store');
    Route::get('/settings/account',         [SettingsController::class, 'account'])->name('settings.account');
    Route::post('/settings/account/update', [SettingsController::class, 'updateAccount'])->name('settings.account.update');
    Route::get('/settings-alias', fn () => redirect()->route('settings.index'))->name('settings');

    /* ===== Notifications ===== */
    Route::get('/notifications',            [NotificationController::class, 'getNotifications'])->name('notifications.index');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markNotificationsAsRead'])->name('notifications.markRead');

    /* ===== Logout ===== */
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
