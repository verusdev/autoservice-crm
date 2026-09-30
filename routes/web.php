<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    /* ---------- Заказ-наряды ---------- */
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('/orders/{order}/works', [OrderController::class, 'addWork'])->name('orders.works.store');
    Route::post('/orders/{order}/parts', [OrderController::class, 'addPart'])->name('orders.parts.store');
    Route::post('/orders/{order}/payments', [OrderController::class, 'addPayment'])->name('orders.payments.store');
    Route::patch('/works/{work}/toggle', [OrderController::class, 'toggleWork'])->name('works.toggle');
    Route::delete('/works/{work}', [OrderController::class, 'destroyWork'])->name('works.destroy');
    Route::delete('/order-parts/{orderPart}', [OrderController::class, 'destroyPart'])->name('order-parts.destroy');
    Route::delete('/payments/{payment}', [OrderController::class, 'destroyPayment'])->name('payments.destroy');

    /* ---------- Клиенты ---------- */
    Route::resource('customers', CustomerController::class);

    /* ---------- Автомобили ---------- */
    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::get('/vehicles/{vehicle}/edit', [VehicleController::class, 'edit'])->name('vehicles.edit');
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');

    /* ---------- Склад ---------- */
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::post('/stock', [StockController::class, 'store'])->name('stock.store');
    Route::delete('/stock/{movement}', [StockController::class, 'destroy'])->name('stock.destroy');
    Route::resource('parts', PartController::class)->except(['show']);

    /* ---------- Финансы ---------- */
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');

    /* ---------- Сотрудники и посты ---------- */
    Route::resource('employees', EmployeeController::class)
        ->parameters(['employees' => 'employee'])
        ->except(['show']);
    Route::resource('posts', PostController::class)->except(['show']);

    /* ---------- Отчёты ---------- */
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
});
