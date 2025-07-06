<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\MaterialController; // ✅

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard
Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

// Guest Routes (Login/Register)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Sales
    Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
    Route::post('/sales', [SalesController::class, 'store'])->name('sales.store');
    Route::get('/sales/{id}/edit', [SalesController::class, 'edit'])->name('sales.edit');
    Route::put('/sales/{id}', [SalesController::class, 'update'])->name('sales.update');
    Route::get('/sales/{id}/receipt', [SalesController::class, 'receipt'])->name('sales.receipt');
    Route::get('/sales-alias', fn () => redirect()->route('sales.index'))->name('sales'); // ✅ alias

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{id}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
    Route::put('/inventory/{id}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::get('/inventory-alias', fn () => redirect()->route('inventory.index'))->name('inventory'); // ✅ alias

    // Employee
    Route::get('/employee', [EmployeeController::class, 'index'])->name('employee.index');
    Route::post('/employee', [EmployeeController::class, 'store'])->name('employee.store');
    Route::get('/employee-alias', fn () => redirect()->route('employee.index'))->name('employee'); // ✅ alias

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'store'])->name('settings.store');
    Route::get('/settings/account', [SettingsController::class, 'account'])->name('settings.account');
    Route::post('/settings/account/update', [SettingsController::class, 'updateAccount'])->name('settings.updateAccount');
    Route::get('/settings-alias', fn () => redirect()->route('settings.index'))->name('settings'); // ✅ alias

    // Production
    Route::get('/production', [ProductionController::class, 'index'])->name('production.index');
    Route::post('/production', [ProductionController::class, 'store'])->name('production.store');
    Route::get('/production/{production}/edit', [ProductionController::class, 'edit'])->name('production.edit');
    Route::put('/production/{id}', [ProductionController::class, 'update'])->name('production.update');
    Route::delete('/production/{production}', [ProductionController::class, 'destroy'])->name('production.destroy');
    Route::get('/production/export/{format}', [ProductionController::class, 'export'])->name('production.export');
    Route::get('/production-alias', fn () => redirect()->route('production.index'))->name('production'); // ✅ alias

    // ✅ Materials Module
    Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');
    Route::post('/materials', [MaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials/{id}/edit', [MaterialController::class, 'edit'])->name('materials.edit');
    Route::put('/materials/{id}', [MaterialController::class, 'update'])->name('materials.update');
    Route::delete('/materials/{id}', [MaterialController::class, 'destroy'])->name('materials.destroy');
    Route::get('/materials-alias', fn () => redirect()->route('materials.index'))->name('materials'); // ✅ alias for sidebar

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
