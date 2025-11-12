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

    // legacy alias
    Route::redirect('/settings/notifications', '/notifications')->name('settings.notifications');
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

    // Appearance
    Route::get('/settings/appearance',       [SettingsController::class, 'appearance'])->name('settings.appearance');
    Route::post('/settings/appearance',      [SettingsController::class, 'appearanceUpdate'])->name('settings.appearance.update');
    Route::get('/settings/appearance/reset', [SettingsController::class, 'appearanceReset'])->name('settings.appearance.reset');

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

        // Soft-delete restore + export
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

    // ===== Products (catalog + BOM management) =====
    Route::resource('products', ProductController::class);
    Route::post('products/quick-store',     [ProductController::class, 'quickStore'])->name('products.quick-store');
    Route::post('products/{product}/image', [ProductController::class, 'updateImage'])
        ->whereNumber('product')->name('products.image.update');
    Route::post('products/{id}/archive',    [ProductController::class, 'archive'])
        ->whereNumber('id')->name('products.archive');

    // BOM / Recipe (under products/{product}/materials)
    Route::prefix('products/{product}')->whereNumber('product')->group(function () {
        Route::get('/materials',             [ProductRecipeController::class, 'index'])->name('products.materials.index');
        Route::get('/materials/defaults',    [ProductRecipeController::class, 'defaults'])->name('products.materials.defaults');
        Route::post('/materials',            [ProductRecipeController::class, 'store'])->name('products.materials.store');
        Route::delete('/materials/{line}',   [ProductRecipeController::class, 'destroy'])
            ->whereNumber('line')->name('products.materials.destroy'); // FIXED: correct controller

        // Legacy aliases (optional, keep if referenced)
        Route::post('/recipe',               [ProductController::class, 'recipeStore'])->name('products.recipe.store');
        Route::delete('/recipe/{line}',      [ProductController::class, 'recipeDestroy'])
            ->whereNumber('line')->name('products.recipe.destroy');
    });

    // ===== Production / Batches =====
    Route::prefix('production')->name('production.')->controller(ProductionController::class)->group(function () {
        Route::get('/',               'index')->name('index');
        Route::get('/filter',         'filter')->name('filter'); // AJAX cards refresh
        Route::get('/info/{name}',    'getProductInfo')->name('info');

        // APIs used by Sales modal and dashboards
        Route::get('/api/by-product/{product}', 'apiByProduct')->whereNumber('product')->name('api.byProduct');
        Route::get('/{product}/batches',        'apiByProduct')->whereNumber('product')->name('batches.byProduct');

        // Lightweight types endpoints for chips & modals
        Route::get('/sales-types', 'salesTypes')->name('sales.types');              // ?product_id=123
        Route::get('/{parent}/types', 'suggestTypes')->whereNumber('parent')->name('types');

        // Orders under a parent product
        Route::get('/orders/{id}', 'showOrders')->whereNumber('id')->name('orders');
        Route::post('/orders',     'storeOrder')->name('orders.store');
        Route::post('/orders/legacy', 'storeOrder')->name('storeOrder');

        // Create + store production
        Route::get('/create', 'create')->name('create');
        Route::post('/',      'store')->name('store');

        // Edit/update/delete batch
        Route::get('/{id}/edit',       'edit')->whereNumber('id')->name('edit');
        Route::put('/{id}',            'update')->whereNumber('id')->name('update');
        Route::delete('/{production}', 'destroy')->whereNumber('production')->name('destroy');
        Route::delete('/batch/latest/{product}', 'destroyLatest')->whereNumber('product')->name('batch.destroyLatest');

        // Archive (list + actions)
        Route::get('/archived',        'archivedIndex')->name('archived');
        Route::post('/{id}/archive',   'archive')->whereNumber('id')->name('archive');
        Route::post('/{id}/restore',   'restore')->whereNumber('id')->name('restore');
        Route::delete('/{id}/force',   'destroyForever')->whereNumber('id')->name('force');

        // PDF (keep before catch-all show)
        Route::get('/{id}/pdf', 'pdf')->whereNumber('id')->name('pdf');

        // Show a single production/batch/product view (keep last)
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    });

    // Quick-add payload (kept outside to avoid collision with /{id})
    Route::get('/production/quick-add/{product}', [ProductionController::class, 'quickAddPayload'])
        ->whereNumber('product')->name('production.quickAdd');

    // Alias for convenience
    Route::redirect('/production-alias', '/production')->name('production');
});

/*
|--------------------------------------------------------------------------
| Admin + Sales
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', RoleMiddleware::class . ':Admin,Sales'])->group(function () {

    // Sales resource
    Route::resource('sales', SalesController::class)->except(['create', 'show']);

    // Availability endpoint (GET/POST)
    Route::match(['GET', 'POST'], '/inventory/available', [SalesController::class, 'available'])
        ->name('sales.available');

    Route::post('/sales/quick-store', [SalesController::class, 'quickStore'])->name('sales.quickStore');

    Route::get('/sales/{sale}/receipt',  [SalesController::class, 'receipt'])
        ->whereNumber('sale')->name('sales.receipt');
    Route::get('/sales/{sale}/download', [SalesController::class, 'download'])
        ->whereNumber('sale')->name('sales.download');

    // Types for Add Sale modal
    Route::get('/sales/api/types', [SalesController::class, 'apiTypes'])->name('sales.api.types');

    // Sales Archive (Trash)
    Route::get('/sales/archived',               [SalesController::class, 'archivedIndex'])->name('sales.archived');
    Route::patch('/sales/archived/{id}/restore',[SalesController::class, 'restore'])->whereNumber('id')->name('sales.restore');
    Route::delete('/sales/archived/{id}/force', [SalesController::class, 'forceDelete'])->whereNumber('id')->name('sales.forceDelete');
    Route::post('/sales/archived/restore-many', [SalesController::class, 'restoreMany'])->name('sales.restoreMany');
    Route::post('/sales/archived/force-many',   [SalesController::class, 'forceDeleteMany'])->name('sales.forceDeleteMany');

    // Handy aliases
    Route::redirect('/sales-archived', '/sales/archived')->name('sales.archived.alias');
    Route::redirect('/sales-alias',    '/sales')->name('sales');
});

/*
|--------------------------------------------------------------------------
| Admin + Inventory
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', RoleMiddleware::class . ':Admin,Inventory'])->group(function () {

    // ===== Materials =====
    Route::prefix('materials')->name('materials.')->group(function () {
        Route::get('/',          [MaterialController::class, 'index'])->name('index');
        Route::get('/create',    [MaterialController::class, 'create'])->name('create');
        Route::post('/',         [MaterialController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [MaterialController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::match(['put','patch'],'/{id}', [MaterialController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}',   [MaterialController::class, 'destroy'])->whereNumber('id')->name('destroy');

        // JSON helpers with expected names
        Route::patch('/{material}/adjust-quantity', [MaterialController::class, 'adjustQuantity'])
            ->whereNumber('material')->name('adjust');   // used as materials.adjust
        Route::patch('/{material}/set-quantity',    [MaterialController::class, 'setQuantity'])
            ->whereNumber('material')->name('setqty');   // used as materials.setqty
    });

    Route::redirect('/materials-alias', '/materials')->name('materials');

    // ===== Inventory =====
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/',          [InventoryController::class, 'index'])->name('index');
        Route::post('/',         [InventoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [InventoryController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::match(['put','patch'],'/{id}', [InventoryController::class, 'update'])->whereNumber('id')->name('update');
    });
    Route::redirect('/inventory-alias', '/inventory')->name('inventory');

    // ===== Allocations =====
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
