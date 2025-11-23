<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OutstandingController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ItemMasterController;
use App\Http\Controllers\ItemMinimController;
use App\Http\Controllers\KedatanganBarangController;
use App\Http\Controllers\DataPOController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Dashboard Route
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Item Outstanding Routes (formerly Stock Minim)
Route::prefix('item_outstanding')->name('item_outstanding.')->group(function () {
    Route::get('/', [OutstandingController::class, 'index'])->name('index');
    Route::post('/', [OutstandingController::class, 'store'])->name('store');
    Route::put('/note/{id}', [OutstandingController::class, 'updateNote'])->name('updateNote');
    Route::put('/update-follow/{id}', [OutstandingController::class, 'updateFollow'])->name('updateFollow');
    Route::put('/update-pengiriman-tanggal/{id}', [OutstandingController::class, 'updatePengirimanTanggal'])->name('updatePengirimanTanggal');
    Route::put('/update-follow-up/{id}', [OutstandingController::class, 'updateFollowUp'])->name('updateFollowUp');
    Route::put('/update-request-whc/{id}', [OutstandingController::class, 'updateRequestWhc'])->name('updateRequestWhc');
});

// Data Master Routes
Route::prefix('item_master')->name('item_master.')->group(function () {
    Route::get('/', [ItemMasterController::class, 'index'])->name('index');
    Route::post('/import-excel', [ItemMasterController::class, 'importExcel'])->name('importExcel');
    Route::post('/delete-all-items', [ItemMasterController::class, 'deleteAllItems'])->name('deleteAllItems');
    Route::put('/note/{id}', [ItemMasterController::class, 'updateNote'])->name('updateNote');
    Route::put('/{id}', [ItemMasterController::class, 'update'])->name('update');
    Route::delete('/{id}', [ItemMasterController::class, 'destroy'])->name('destroy');
});

// Data PO Routes
Route::prefix('data_po')->name('data_po.')->group(function () {
    Route::get('/', [DataPOController::class, 'index'])->name('index');
    Route::post('/import-excel', [DataPOController::class, 'importExcel'])->name('importExcel');
    Route::post('/delete-all', [DataPOController::class, 'deleteAll'])->name('deleteAll');
    Route::delete('/{id}', [DataPOController::class, 'destroy'])->name('destroy');
});

// Item Minim Routes
Route::prefix('item_minim')->name('item_minim.')->group(function () {
    Route::get('/', [ItemMinimController::class, 'index'])->name('index');
    Route::put('/note/{id}', [ItemMinimController::class, 'updateNote'])->name('updateNote');
    Route::put('/{id}', [ItemMinimController::class, 'update'])->name('update');
    Route::delete('/{id}', [ItemMinimController::class, 'destroy'])->name('destroy');
});

// Kedatangan Barang Routes
Route::prefix('kedatangan_barang')->name('kedatangan_barang.')->group(function () {
    Route::get('/', [KedatanganBarangController::class, 'index'])->name('index');
    Route::post('/import-excel', [KedatanganBarangController::class, 'importExcel'])->name('importExcel');
});

// History Routes
Route::prefix('history')->name('history.')->group(function () {
    Route::get('/', [HistoryController::class, 'index'])->name('index');
    Route::put('/{id}', [HistoryController::class, 'update'])->name('update');
    Route::delete('/{id}', [HistoryController::class, 'destroy'])->name('destroy');
});

// Data Management Routes
Route::get('/form-request-admin', function () {
    return redirect()->route('item_outstanding.index');
})->name('form-request-admin');

Route::get('/form-request-user', function () {
    return redirect()->route('item_outstanding.index');
})->name('form-request-user');

Route::get('/informasi-stock', function () {
    return redirect()->route('item_outstanding.index');
})->name('informasi-stock');

// Data Master Routes (Admin Only)
Route::get('/inventory-dashboard', function () {
    return redirect()->route('item_outstanding.index');
})->name('inventory-dashboard');

Route::get('/add-product', function () {
    return redirect()->route('item_outstanding.index');
})->name('add-product');

Route::get('/inventory-movements', function () {
    return redirect()->route('item_outstanding.index');
})->name('inventory-movements');

// Settings Routes (Admin Only)
Route::get('/user-informasi', function () {
    return redirect()->route('item_outstanding.index');
})->name('user-informasi');

// Information Routes
Route::get('/about', function () {
    return redirect()->route('item_outstanding.index');
})->name('about');

Route::get('/contact', function () {
    return redirect()->route('item_outstanding.index');
})->name('contact');

// Logout Route
Route::post('/logout', function () {
    return redirect()->route('item_outstanding.index');
})->name('logout');
