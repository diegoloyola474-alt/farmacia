<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
});

Route::middleware(['auth', PreventBackHistory::class])->group(function () {
    Route::middleware('role:Administrador,Químico farmacéutico,Cajero')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
        Route::resource('customers', CustomerController::class)->except('show');
        Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
    });

    Route::middleware('role:Administrador,Químico farmacéutico')->group(function () {
        Route::resource('medicines', MedicineController::class)->except('show');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('reports/excel', [ReportController::class, 'excel'])->name('reports.excel');
    });

    Route::middleware('role:Administrador')->group(function () {
        Route::delete('sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');
        Route::resource('roles', RoleController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('categories', CategoryController::class)->except('show');
    });

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
