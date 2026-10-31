<?php

namespace App\Actions\Pesanan;

use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BuatPesananAction
{
    /**
     * @param  array{
     *     kanal: string,
     *     nama_pelanggan: string,
     *     items: array<int, array{
     *         kode_menu: string,
     *         nama_menu: string,
     *         jumlah: int,
     *         harga_satuan: int
     *     }>
     * }  $data
     */
    public function handle(array $data): Pesanan
    {
        return DB::transaction(function () use ($data): Pesanan {
            $items = array_map(
                static fn (array $item): array => [
                    ...$item,
                    'subtotal' => $item['jumlah'] * $item['harga_satuan'],
                ],
                $data['items'],
            );

            $pesanan = Pesanan::create([
                'kode_pesanan' => Str::uuid()->toString(),
                'kanal' => $data['kanal'],
                'nama_pelanggan' => $data['nama_pelanggan'],
                'status' => 'menunggu',
                'total' => array_sum(array_column($items, 'subtotal')),
            ]);

            $pesanan->update([
                'kode_pesanan' => sprintf('PSN-%06d', $pesanan->getKey()),
            ]);

            $pesanan->items()->createMany($items);

            return $pesanan->load('items');
        });
    }
}
