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
use App\Http\Controllers\ProductRecipeController; // ← add this

/* ----------------------------------------------------------------------------
 | Global route param patterns (avoid accidental collisions)
 * ---------------------------------------------------------------------------*/
Route::pattern('id', '[0-9]+');
Route::pattern('product', '[0-9]+');
Route::pattern('production', '[0-9]+');
Route::pattern('sale', '[0-9]+');
Route::pattern('allocation', '[0-9]+');
Route::pattern('item', '[0-9]+');
Route::pattern('line', '[0-9]+');

/* Landing */
Route::redirect('/', '/dashboard')->name('home');

/* ----------------------------------------------------------------------------
 | Guest (Auth)
 * ---------------------------------------------------------------------------*/
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login',   [AuthController::class, 'login'])->name('login.submit');

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register',[AuthController::class, 'register'])->name('register.submit');
});

/* ----------------------------------------------------------------------------
 | Authenticated
 * ---------------------------------------------------------------------------*/
Route::middleware('auth')->group(function () {

    /* ===== Dashboard ===== */
    Route::get('/dashboard',      [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

    /* ===== Products ===== */
    Route::resource('products', ProductController::class);
    Route::post('products/quick-store',        [ProductController::class, 'quickStore'])->name('products.quick-store');
    Route::post('products/{product}/image',    [ProductController::class, 'updateImage'])->whereNumber('product')->name('products.image.update');
    Route::post('products/{id}/archive',       [ProductController::class, 'archive'])->whereNumber('id')->name('products.archive');

    /* ----- Product ↔ Materials (Recipes / BOM) ----- */
    Route::prefix('products/{product}')
        ->whereNumber('product')
        ->group(function () {
            // Use ProductRecipeController for materials pages + saving
            Route::get('/materials',                [ProductRecipeController::class, 'index'])->name('products.materials.index');
            Route::get('/materials/defaults',       [ProductRecipeController::class, 'defaults'])->name('products.materials.defaults');
            Route::post('/materials',               [ProductRecipeController::class, 'store'])->name('products.materials.store');

            // Delete a single line — keep existing handler in ProductController (already implemented)
            Route::delete('/materials/{line}',      [ProductController::class, 'recipeDestroy'])
                ->whereNumber('line')->name('products.materials.destroy');

            // Backward-compat aliases (older blades/controllers)
            Route::post('/recipe',                  [ProductController::class, 'recipeStore'])->name('products.recipe.store');
            Route::delete('/recipe/{line}',         [ProductController::class, 'recipeDestroy'])
                ->whereNumber('line')->name('products.recipe.destroy');
        });

    /* ===== Production / Batches ===== */
    Route::prefix('production')->name('production.')->controller(ProductionController::class)->group(function () {
        Route::get('/',                   'index')->name('index');
        Route::get('/filter',             'filter')->name('filter');

        // Static first
        Route::get('/info/{name}',        'getProductInfo')->name('info'); // product name (string)
        Route::get('/api/by-product/{product}', 'apiByProduct')->whereNumber('product')->name('api.byProduct');
        Route::get('/{product}/batches',  'apiByProduct')->whereNumber('product')->name('batches');

        // Orders
        Route::get('/orders/{id}',        'showOrders')->whereNumber('id')->name('orders');
        Route::post('/orders',            'storeOrder')->name('orders.store');
        Route::post('/orders/legacy',     'storeOrder')->name('storeOrder'); // legacy alias

        // Create production batch
        Route::post('/',                  'store')->name('store');

        // Edit / update / destroy / show
        Route::get('/{id}/edit',          'edit')->whereNumber('id')->name('edit');
        Route::put('/{id}',               'update')->whereNumber('id')->name('update');
        Route::delete('/{production}',    'destroy')->whereNumber('production')->name('destroy');
        Route::delete('/batch/latest/{product}', 'destroyLatest')->whereNumber('product')->name('batch.destroyLatest');

        Route::get('/{id}',               'show')->whereNumber('id')->name('show');
    });

    // Explicit quick-add payload
    Route::get('/production/quick-add/{product}', [ProductionController::class, 'quickAddPayload'])
        ->whereNumber('product')->name('production.quickAdd');

    // Alias so route('production') works
    Route::get('/production-alias', fn () => redirect()->route('production.index'))->name('production');

    /* ===== Sales ===== */
    Route::resource('sales', SalesController::class)->except(['create','show']);
    Route::post('/inventory/available', [SalesController::class, 'available'])->name('sales.available');
    Route::post('/sales/quick-store',   [SalesController::class, 'quickStore'])->name('sales.quickStore');
    Route::get('/sales/{sale}/receipt',  [SalesController::class, 'receipt'])->whereNumber('sale')->name('sales.receipt');
    Route::get('/sales/{sale}/download', [SalesController::class, 'download'])->whereNumber('sale')->name('sales.download');
    Route::get('/sales-alias', fn () => redirect()->route('sales.index'))->name('sales');

    /* ===== Allocations ===== */
    Route::prefix('allocations')->name('allocations.')->group(function () {
        Route::patch('/{allocation}/approve',    [BatchAllocationController::class, 'approve'])->whereNumber('allocation')->name('approve');
        Route::patch('/{allocation}/release',    [BatchAllocationController::class, 'release'])->whereNumber('allocation')->name('release');
        Route::patch('/{allocation}/reallocate', [BatchAllocationController::class, 'reallocate'])->whereNumber('allocation')->name('reallocate');
        Route::delete('/{allocation}',           [BatchAllocationController::class, 'destroy'])->whereNumber('allocation')->name('destroy');
        Route::get('/by-item/{item}',            [BatchAllocationController::class, 'byItem'])->whereNumber('item')->name('byItem');
    });

    /* ===== Materials ===== */
    Route::prefix('materials')->name('materials.')->group(function () {
        Route::get('/create',      [MaterialController::class, 'create'])->name('create');
        Route::get('/',            [MaterialController::class, 'index'])->name('index');
        Route::post('/',           [MaterialController::class, 'store'])->name('store');
        Route::post('/store',      [MaterialController::class, 'store'])->name('store.alias'); // legacy
        Route::get('/{id}/edit',   [MaterialController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}',        [MaterialController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}',     [MaterialController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });
    Route::get('/materials-alias', fn () => redirect()->route('materials.index'))->name('materials');

    /* ===== Inventory ===== */
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/',            [InventoryController::class, 'index'])->name('index');
        Route::post('/',           [InventoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit',   [InventoryController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}',        [InventoryController::class, 'update'])->whereNumber('id')->name('update');
    });
    Route::get('/inventory-alias', fn () => redirect()->route('inventory.index'))->name('inventory');

    /* ===== Employees ===== */
    Route::prefix('employees')->name('employees.')->controller(EmployeeController::class)->group(function () {
        Route::get('/',           'index')->name('index');
        Route::post('/',          'store')->name('store');
        Route::get('/{id}/edit',  'edit')->whereNumber('id')->name('edit');
        Route::put('/{id}',       'update')->whereNumber('id')->name('update');
        Route::delete('/{id}',    'destroy')->whereNumber('id')->name('destroy');
        Route::patch('/{id}/toggle-block', 'toggleBlock')->whereNumber('id')->name('toggle-block');
        Route::get('/{id}',       'show')->whereNumber('id')->name('show'); // keep last
    });

    /* 🔁 Legacy employee aliases (for old links / sidebars) */
    Route::get('/employee',       fn () => redirect()->route('employees.index'))->name('employee.index');
    Route::get('/employee-alias', fn () => redirect()->route('employees.index'))->name('employee');  // fixes Route [employee] not defined
    Route::get('/employees-alias',fn () => redirect()->route('employees.index'))->name('employees');

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

/* Optional: fallback */
Route::fallback(function () {
    return redirect()->route('dashboard');
});
