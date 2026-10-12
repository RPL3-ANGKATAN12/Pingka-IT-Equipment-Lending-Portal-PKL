<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LoansController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\ReturnedItemController;

Route::get('/', [HomeController::class, 'index'])
    ->name('guest.home');

Route::get('/items', [LoansController::class, 'items'])
    ->name('guest.items');

Route::get('/loans', [LoansController::class, 'index'])
    ->name('guest.loans');

Route::get('/login', function () {

    if (
        auth()->check() &&
        auth()->user()->role === 'admin'
    ) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('guest.home');

})->name('login');

Route::post(
    '/login/admin',
    [AuthController::class, 'loginAdmin']
)->name('login.admin');

Route::post(
    '/logout',
    [AuthController::class, 'logout']
)
    ->middleware('auth')
    ->name('logout');

Route::get(
    '/request',
    [LoansController::class, 'create']
)->name('loans.create');

Route::post(
    '/request',
    [LoansController::class, 'store']
)->name('loans.store');

Route::get(
    '/track/{token}',
    [LoansController::class, 'track']
)->name('loans.track');

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get(
            '/dashboard',
            [AdminController::class, 'dashboard']
        )->name('dashboard');

        Route::get(
            '/requests',
            [AdminController::class, 'requests']
        )->name('requests');

        Route::post(
            '/requests/{request}/approve',
            [AdminController::class, 'approveRequest']
        )->name('requests.approve');

        Route::post(
            '/requests/{request}/reject',
            [AdminController::class, 'rejectRequest']
        )->name('requests.reject');

        Route::delete(
            '/requests/{request}',
            [AdminController::class, 'deleteRequest']
        )->name('requests.delete');

        Route::get(
            '/loans',
            [AdminController::class, 'loans']
        )->name('loans');

        Route::put(
            '/loans/{loan}',
            [AdminController::class, 'updateLoan']
        )->name('loans.update');

        Route::put(
            '/loans/{loan}/status',
            [AdminController::class, 'updateStatus']
        )->name('loans.update-status');

        Route::get(
            '/items',
            [ItemController::class, 'index']
        )->name('items.index');

        Route::get(
            '/items/create',
            [ItemController::class, 'create']
        )->name('items.create');

        Route::post(
            '/items',
            [ItemController::class, 'store']
        )->name('items.store');

        Route::get(
            '/items/{item}/edit',
            [ItemController::class, 'edit']
        )->name('items.edit');

        Route::put(
            '/items/{item}',
            [ItemController::class, 'update']
        )->name('items.update');

        Route::delete(
            '/items/{item}',
            [ItemController::class, 'destroy']
        )->name('items.destroy');

        Route::get('/returned-items', [ReturnedItemController::class, 'index'])
            ->name('returned-items.index');

        Route::put('/returned-items/{loan}/due-date', [ReturnedItemController::class, 'updateDueDate'])
            ->name('returned-items.due-date');

        Route::post('/returned-items/{loan}/return', [ReturnedItemController::class, 'markReturned'])
            ->name('returned-items.return');

    });