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
use App\Http\Controllers\ProductRecipeController;
use App\Http\Controllers\FallbackController;
use App\Http\Controllers\UserManagementController;

use App\Http\Middleware\RoleMiddleware;

/*
|--------------------------------------------------------------------------
| Global route param patterns
|--------------------------------------------------------------------------
*/
Route::pattern('id', '[0-9]+');
Route::pattern('user', '[0-9]+');
Route::pattern('product', '[0-9]+');
Route::pattern('production', '[0-9]+');
Route::pattern('sale', '[0-9]+');
Route::pattern('allocation', '[0-9]+');
Route::pattern('item', '[0-9]+');
Route::pattern('line', '[0-9]+');

/* Landing */
Route::redirect('/', '/dashboard')->name('home');

/*
|--------------------------------------------------------------------------
| Guest (Auth)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',     [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login',    [AuthController::class, 'login'])->middleware('throttle:20,1')->name('login.submit');

    Route::get('/register',  [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

/*
|--------------------------------------------------------------------------
| Authenticated (all signed-in users)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard',      [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

    // Settings
    Route::get('/settings',                 [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/account',         [SettingsController::class, 'account'])->name('settings.account');
    Route::post('/settings',                [SettingsController::class, 'store'])->name('settings.store');
    Route::post('/settings/account/update', [SettingsController::class, 'updateAccount'])->name('settings.account.update');
    Route::redirect('/settings-alias', '/settings')->name('settings');

    // Notifications
    Route::get('/notifications',            [NotificationController::class, 'getNotifications'])->name('notifications.index');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markNotificationsAsRead'])->name('notifications.markRead');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Admin-only
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', RoleMiddleware::class . ':Admin'])->group(function () {

    // ----- User Management -----
    Route::prefix('users')->name('users.')->controller(UserManagementController::class)->group(function () {
        Route::get('/',            'index')->name('index');
        Route::post('/',           'store')->name('store');
        Route::get('/{user}/edit', 'edit')->whereNumber('user')->name('edit');
        Route::put('/{user}',      'update')->whereNumber('user')->name('update');
        Route::delete('/{user}',   'destroy')->whereNumber('user')->name('destroy');

        // UX helpers
        Route::patch('/{user}/toggle-active',  'toggleActive')->whereNumber('user')->name('toggle-active');
        Route::post('/{user}/reset-password',  'resetPassword')->whereNumber('user')->name('reset-password');

        // soft-delete restore + export
        Route::patch('/{user}/restore', 'restore')->whereNumber('user')->name('restore');
        Route::get('/export/csv',       'exportCsv')->name('export.csv');
    });

    // ----- Employees -----
    Route::prefix('employees')->name('employees.')->controller(EmployeeController::class)->group(function () {
        Route::get('/',                   'index')->name('index');
        Route::post('/',                  'store')->name('store');
        Route::get('/{id}/edit',          'edit')->whereNumber('id')->name('edit');
        Route::put('/{id}',               'update')->whereNumber('id')->name('update');
        Route::delete('/{id}',            'destroy')->whereNumber('id')->name('destroy');
        Route::patch('/{id}/toggle-block','toggleBlock')->whereNumber('id')->name('toggle-block');
        Route::get('/{id}',               'show')->whereNumber('id')->name('show'); // keep last
    });

    // Legacy employee aliases
    Route::redirect('/employee',        '/employees')->name('employee.index');
    Route::redirect('/employee-alias',  '/employees')->name('employee');
    Route::redirect('/employees-alias', '/employees')->name('employees');
});

/*
|--------------------------------------------------------------------------
| Admin + Production Manager
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', RoleMiddleware::class . ':Admin,Production'])->group(function () {

    // ----- Products (catalog + BOM management) -----
    Route::resource('products', ProductController::class);
    Route::post('products/quick-store',     [ProductController::class, 'quickStore'])->name('products.quick-store');
    Route::post('products/{product}/image', [ProductController::class, 'updateImage'])
        ->whereNumber('product')->name('products.image.update');
    Route::post('products/{id}/archive',    [ProductController::class, 'archive'])
        ->whereNumber('id')->name('products.archive');

    // BOM / Recipe
    Route::prefix('products/{product}')->whereNumber('product')->group(function () {
        Route::get('/materials',          [ProductRecipeController::class, 'index'])->name('products.materials.index');
        Route::get('/materials/defaults', [ProductRecipeController::class, 'defaults'])->name('products.materials.defaults');
        Route::post('/materials',         [ProductRecipeController::class, 'store'])->name('products.materials.store');

        Route::delete('/materials/{line}', [ProductController::class, 'recipeDestroy'])
            ->whereNumber('line')->name('products.materials.destroy');

        // Legacy aliases
        Route::post('/recipe',          [ProductController::class, 'recipeStore'])->name('products.recipe.store');
        Route::delete('/recipe/{line}', [ProductController::class, 'recipeDestroy'])
            ->whereNumber('line')->name('products.recipe.destroy');
    });

    // ===== Production / Batches =====
    Route::prefix('production')->name('production.')->controller(ProductionController::class)->group(function () {
    Route::get('/',             'index')->name('index');
    Route::get('/filter',       'filter')->name('filter');
    Route::get('/info/{name}',  'getProductInfo')->name('info');

    Route::get('/api/by-product/{product}', 'apiByProduct')->whereNumber('product')->name('api.byProduct');
    Route::get('/{product}/batches',        'apiByProduct')->whereNumber('product')->name('batches.byProduct');

    Route::get('/orders/{id}', 'showOrders')->whereNumber('id')->name('orders');
    Route::post('/orders',     'storeOrder')->name('orders.store');
    Route::post('/orders/legacy', 'storeOrder')->name('storeOrder');

    Route::post('/', 'store')->name('store');

    Route::get('/{id}/edit',       'edit')->whereNumber('id')->name('edit');
    Route::put('/{id}',            'update')->whereNumber('id')->name('update');
    Route::delete('/{production}', 'destroy')->whereNumber('production')->name('destroy');
    Route::delete('/batch/latest/{product}', 'destroyLatest')->whereNumber('product')->name('batch.destroyLatest');

    // ✅ NEW:
    Route::get('/{id}/pdf',        'pdf')->whereNumber('id')->name('pdf');
    // Create form for new production batch
    Route::get('/create', 'create')->name('create');

    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    // routes/web.php
    Route::get('/production/{parent}/types', [\App\Http\Controllers\ProductionController::class, 'suggestTypes'])
    ->name('production.types');
    // routes/web.php
    Route::get('/production/{parent}/types', [\App\Http\Controllers\ProductionController::class, 'suggestTypes'])
    ->name('production.types');

});


    // Quick-add payload (kept outside to avoid collision with /{id})
    Route::get('/production/quick-add/{product}', [ProductionController::class, 'quickAddPayload'])
        ->whereNumber('product')->name('production.quickAdd');

    // Alias
    Route::redirect('/production-alias', '/production')->name('production');
});

/*
|--------------------------------------------------------------------------
| Admin + Sales
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', RoleMiddleware::class . ':Admin,Sales'])->group(function () {

    Route::resource('sales', SalesController::class)->except(['create', 'show']);

    // Availability endpoint (GET/POST)
    Route::match(['GET', 'POST'], '/inventory/available', [SalesController::class, 'available'])
        ->name('sales.available');

    Route::post('/sales/quick-store', [SalesController::class, 'quickStore'])->name('sales.quickStore');
    Route::get('/sales/{sale}/receipt',  [SalesController::class, 'receipt'])
        ->whereNumber('sale')->name('sales.receipt');
    Route::get('/sales/{sale}/download', [SalesController::class, 'download'])
        ->whereNumber('sale')->name('sales.download');

    // Alias
    Route::redirect('/sales-alias', '/sales')->name('sales');
});

/*
|--------------------------------------------------------------------------
| Admin + Inventory
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', RoleMiddleware::class . ':Admin,Inventory'])->group(function () {

    // ===== Materials =====
    Route::prefix('materials')->name('materials.')->group(function () {
        Route::get('/create',    [MaterialController::class, 'create'])->name('create');
        Route::get('/',          [MaterialController::class, 'index'])->name('index');
        Route::post('/',         [MaterialController::class, 'store'])->name('store');
        Route::post('/store',    [MaterialController::class, 'store'])->name('store.alias'); // legacy
        Route::get('/{id}/edit', [MaterialController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}',      [MaterialController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}',   [MaterialController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });
    Route::redirect('/materials-alias', '/materials')->name('materials');

    // ===== Inventory =====
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/',          [InventoryController::class, 'index'])->name('index');
        Route::post('/',         [InventoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [InventoryController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}',      [InventoryController::class, 'update'])->whereNumber('id')->name('update');
    });
    Route::redirect('/inventory-alias', '/inventory')->name('inventory');

    // ===== Allocations (kept with inventory ops) =====
    Route::prefix('allocations')->name('allocations.')->group(function () {
        Route::patch('/{allocation}/approve',    [BatchAllocationController::class, 'approve'])
            ->whereNumber('allocation')->name('approve');
        Route::patch('/{allocation}/release',    [BatchAllocationController::class, 'release'])
            ->whereNumber('allocation')->name('release');
        Route::patch('/{allocation}/reallocate', [BatchAllocationController::class, 'reallocate'])
            ->whereNumber('allocation')->name('reallocate');
        Route::delete('/{allocation}',           [BatchAllocationController::class, 'destroy'])
            ->whereNumber('allocation')->name('destroy');
        Route::get('/by-item/{item}',            [BatchAllocationController::class, 'byItem'])
            ->whereNumber('item')->name('byItem');
    });
});

/*
|--------------------------------------------------------------------------
| Fallback (single-action controller)
|--------------------------------------------------------------------------
*/
Route::fallback(FallbackController::class);
