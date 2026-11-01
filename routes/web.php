<?php

use App\Http\Controllers\AdminCashierController;
use App\Http\Controllers\AdminCatalogController;
use App\Http\Controllers\AdminMasterController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('storefront.home');
Route::post('/custom-order', [StorefrontController::class, 'customOrder'])->middleware('throttle:5,1')->name('storefront.custom-order');
Route::get('/login-animation', function () {
    $animation = storage_path('app/public/Login Character Animation.lottie');

    abort_unless(is_file($animation), 404);

    return response()->file($animation, [
        'Content-Type' => 'application/zip',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('login.animation');
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/catalog', [AdminCatalogController::class, 'index'])->name('catalog.index');
    Route::get('/master/offerings', [AdminCatalogController::class, 'offerings'])->name('offerings.index');
    Route::get('/offerings/{productOffering}', [AdminCatalogController::class, 'offeringDetail'])->name('offerings.show');
    Route::get('/cashier', [AdminCashierController::class, 'index'])->name('cashier.index');
    Route::get('/transactions', [AdminCashierController::class, 'transactions'])->name('transactions.index');
    Route::get('/transactions/{transaction}/receipt', [AdminCashierController::class, 'receipt'])->name('transactions.receipt');
    Route::patch('/transactions/{transaction}/inactive', [AdminCashierController::class, 'markTransactionInactive'])->name('transactions.inactive');
    Route::patch('/transactions/{transaction}/logged-out', [AdminCashierController::class, 'markTransactionLoggedOut'])->name('transactions.logged-out');
    Route::get('/transactions/{transaction}', [AdminCashierController::class, 'showTransaction'])->name('transactions.show');
    Route::put('/transactions/{transaction}', [AdminCashierController::class, 'updateTransaction'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [AdminCashierController::class, 'destroyTransaction'])->name('transactions.destroy');
    Route::get('/reports', [AdminCashierController::class, 'reports'])->name('reports.index');
    Route::get('/reports/export/pdf', [AdminCashierController::class, 'exportTransactionsPdf'])->name('reports.export.pdf');
    Route::get('/cashier/api/users', [AdminCashierController::class, 'users'])->name('cashier.api.users');
    Route::get('/cashier/api/offerings', [AdminCashierController::class, 'offerings'])->name('cashier.api.offerings');
    Route::post('/cashier/api/transactions', [AdminCashierController::class, 'store'])->name('cashier.api.transactions.store');
    Route::get('/master/products', [AdminCatalogController::class, 'products'])->name('products.index');
    Route::get('/master/account-types', [AdminMasterController::class, 'accountTypes'])->name('account-types.index');
    Route::get('/master/users', [AdminMasterController::class, 'users'])->name('users.index');
    Route::get('/settings', [AdminMasterController::class, 'settings'])->name('settings.index');
    Route::put('/settings', [AdminMasterController::class, 'updateSettings'])->name('settings.update');
    Route::post('/products', [AdminCatalogController::class, 'storeProduct'])->name('products.store');
    Route::put('/products/{product}', [AdminCatalogController::class, 'updateProduct'])->name('products.update');
    Route::delete('/products/{product}', [AdminCatalogController::class, 'destroyProduct'])->name('products.destroy');
    Route::post('/account-types', [AdminCatalogController::class, 'storeAccountType'])->name('account-types.store');
    Route::post('/users', [AdminMasterController::class, 'storeUser'])->name('users.store');
    Route::post('/offerings', [AdminCatalogController::class, 'storeOffering'])->name('offerings.store');
    Route::put('/offerings/{productOffering}', [AdminCatalogController::class, 'updateOffering'])->name('offerings.update');
    Route::patch('/offerings/{productOffering}/status', [AdminCatalogController::class, 'updateOfferingStatus'])->name('offerings.status');
    Route::post('/offerings/{productOffering}/users', [AdminCatalogController::class, 'addOfferingUser'])->name('offerings.users.store');
    Route::delete('/offerings/{productOffering}', [AdminCatalogController::class, 'destroyOffering'])->name('offerings.destroy');
    Route::patch('/products/{product}/toggle', [AdminCatalogController::class, 'toggleProduct'])->name('products.toggle');
    Route::patch('/account-types/{accountType}/toggle', [AdminCatalogController::class, 'toggleAccountType'])->name('account-types.toggle');
});
