<?php

use App\Http\Controllers\Api\V1\OperasionalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('pesanan', [OperasionalController::class, 'index'])
        ->name('operasional.pesanan.index');

    Route::get('pesanan/{kode}', [OperasionalController::class, 'show'])
        ->name('operasional.pesanan.show');

    Route::patch(
        'pesanan/{kode}/status',
        [OperasionalController::class, 'updateStatus'],
    )->name('operasional.pesanan.status.update');
});
