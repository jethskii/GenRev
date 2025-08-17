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

Route::redirect('/', '/dashboard')->name('home');

/* -------------------------- Guest (Auth) -------------------------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

/* ------------------------ Authenticated -------------------------- */
Route::middleware('auth')->group(function () {

    /* --------------------------- Dashboard --------------------------- */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

    /* ---------------------------- Products --------------------------- */
    Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {
        Route::get('/', 'index')->name('index');                       // products.index
        Route::post('/', 'store')->name('store');                      // products.store
        Route::post('/{id}/archive', 'archive')->whereNumber('id')->name('archive');
        Route::put('/{id}/update-image', 'updateImage')->whereNumber('id')->name('update.image');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
        Route::post('/quick-store', 'quickStore')->name('quick-store');
    });

    // Product-scoped Materials (Recipe/BOM)
    Route::prefix('products/{product}')->name('products.')->group(function () {
        Route::get('materials',        [ProductRecipeController::class, 'index'])->name('materials.index');
        Route::post('materials',       [ProductRecipeController::class, 'storeOrUpdate'])->name('materials.store');
        Route::get('materials/defaults',[ProductRecipeController::class, 'defaults'])->name('materials.defaults');
    });

    /* ------------------------ Production Routes ---------------------- */
    Route::prefix('production')->name('production.')->controller(ProductionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/filter', 'filter')->name('filter');
        Route::get('/info/{name}', 'getProductInfo')->name('info');
        Route::get('/api/by-product/{product}', 'apiByProduct')->whereNumber('product')->name('api.byProduct');
        Route::get('/orders/{id}', 'showOrders')->whereNumber('id')->name('orders');
        Route::post('/{product}/order', 'storeOrder')->whereNumber('product')->name('storeOrder');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');
        Route::put('/{id}', 'update')->whereNumber('id')->name('update');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
    });
    Route::get('/production-alias', fn () => redirect()->route('production.index'))->name('production');

    /* ------------------------------ Sales ---------------------------- */
    Route::get('/inventory/available', [SalesController::class, 'available'])->name('sales.available');
    Route::resource('sales', SalesController::class)->except(['create', 'show']);
    Route::get('/sales-alias', fn () => redirect()->route('sales.index'))->name('sales');

    /* ---------------------- Batch Allocations ------------------------ */
    Route::prefix('allocations')->name('allocations.')->group(function () {
        Route::patch('/{allocation}/approve', [BatchAllocationController::class, 'approve'])->whereNumber('allocation')->name('approve');
        Route::patch('/{allocation}/release', [BatchAllocationController::class, 'release'])->whereNumber('allocation')->name('release');
        Route::patch('/{allocation}/reallocate', [BatchAllocationController::class, 'reallocate'])->whereNumber('allocation')->name('reallocate');
        Route::delete('/{allocation}', [BatchAllocationController::class, 'destroy'])->whereNumber('allocation')->name('destroy');
        Route::get('/by-item/{item}', [BatchAllocationController::class, 'byItem'])->whereNumber('item')->name('byItem');
    });

    /* ---------------------------- Inventory -------------------------- */
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{id}/edit', [InventoryController::class, 'edit'])->whereNumber('id')->name('inventory.edit');
    Route::put('/inventory/{id}', [InventoryController::class, 'update'])->whereNumber('id')->name('inventory.update');
    Route::get('/inventory-alias', fn () => redirect()->route('inventory.index'))->name('inventory');

    /* ----------------------------- Employee -------------------------- */
    Route::get('/employee', [EmployeeController::class, 'index'])->name('employee.index');
    Route::post('/employee', [EmployeeController::class, 'store'])->name('employee.store');
    Route::get('/employee-alias', fn () => redirect()->route('employee.index'))->name('employee');

    /* ----------------------------- Settings -------------------------- */
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'store'])->name('settings.store');
    Route::get('/settings/account', [SettingsController::class, 'account'])->name('settings.account');
    Route::post('/settings/account/update', [SettingsController::class, 'updateAccount'])->name('settings.account.update');
    Route::get('/settings-alias', fn () => redirect()->route('settings.index'))->name('settings');

    /* ----------------------------- Materials ------------------------- */
    Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');
    Route::post('/materials', [MaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials/{id}/edit', [MaterialController::class, 'edit'])->whereNumber('id')->name('materials.edit');
    Route::put('/materials/{id}', [MaterialController::class, 'update'])->whereNumber('id')->name('materials.update');
    Route::delete('/materials/{id}', [MaterialController::class, 'destroy'])->whereNumber('id')->name('materials.destroy');
    Route::get('/materials-alias', fn () => redirect()->route('materials.index'))->name('materials');

    /* --------------------------- Notifications ----------------------- */
    Route::get('/notifications', [NotificationController::class, 'getNotifications'])->name('notifications.index');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markNotificationsAsRead'])->name('notifications.markRead');

    /* ------------------------------ Logout --------------------------- */
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
