<?php

namespace App\Actions\Pesanan;

use App\Exceptions\PesananTidakDapatDibatalkanException;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;

class BatalkanPesananAction
{
    public function handle(string $kode): Pesanan
    {
        return DB::transaction(function () use ($kode): Pesanan {
            $pesanan = Pesanan::query()
                ->where('kode_pesanan', $kode)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pesanan->status !== 'menunggu') {
                throw new PesananTidakDapatDibatalkanException(
                    kodePesanan: $pesanan->kode_pesanan,
                    statusPesanan: $pesanan->status,
                );
            }

            $pesanan->update([
                'status' => 'dibatalkan',
            ]);

            return $pesanan->refresh();
        });
    }
}
