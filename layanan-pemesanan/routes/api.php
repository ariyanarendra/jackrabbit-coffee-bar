<?php

use App\Http\Controllers\Api\V1\PesananController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('pesanan', [PesananController::class, 'store'])
        ->name('pesanan.store');

    Route::get('pesanan/{kode}', [PesananController::class, 'show'])
        ->name('pesanan.show');

    Route::post(
        'pesanan/{kode}/konfirmasi',
        [PesananController::class, 'konfirmasi'],
    )->name('pesanan.konfirmasi');

    Route::post(
        'pesanan/{kode}/batal',
        [PesananController::class, 'batal'],
    )->name('pesanan.batal');
});
