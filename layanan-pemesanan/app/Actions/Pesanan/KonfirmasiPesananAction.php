<?php

namespace App\Actions\Pesanan;

use App\Exceptions\StatusPesananTidakValidException;
use App\Integrations\Inventori\InventoriClient;
use App\Messaging\PesananDikonfirmasiPublisher;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;

class KonfirmasiPesananAction
{
    public function __construct(
        private readonly InventoriClient $inventori,
        private readonly PesananDikonfirmasiPublisher $publisher,
    ) {}

    public function handle(string $kode): Pesanan
    {
        return DB::transaction(function () use ($kode): Pesanan {
            $pesanan = Pesanan::query()
                ->where('kode_pesanan', $kode)
                ->with('items')
                ->lockForUpdate()
                ->firstOrFail();

            if ($pesanan->status !== 'menunggu') {
                throw new StatusPesananTidakValidException(
                    kodePesanan: $pesanan->kode_pesanan,
                    statusPesanan: $pesanan->status,
                );
            }

            $this->inventori->kurangiStok($pesanan);

            $pesanan->update([
                'status' => 'dikonfirmasi',
            ]);

            $pesanan->refresh()->load('items');

            $this->publisher->publish($pesanan);

            return $pesanan;
        });
    }
}
