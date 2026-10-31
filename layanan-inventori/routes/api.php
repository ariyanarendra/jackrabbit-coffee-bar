<?php

use App\Http\Controllers\Api\V1\InventoriController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('menu/{kode}', [InventoriController::class, 'show'])
        ->name('inventori.menu.show');

    Route::post('stok/cek', [InventoriController::class, 'cekStok'])
        ->name('inventori.stok.cek');

    Route::post('stok/kurangi', [InventoriController::class, 'kurangiStok'])
        ->name('inventori.stok.kurangi');
});
