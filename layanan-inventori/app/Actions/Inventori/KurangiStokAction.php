<?php

namespace App\Actions\Inventori;

use App\Exceptions\KonflikIdempotensiException;
use App\Exceptions\StokTidakMencukupiException;
use App\Models\Menu;
use App\Models\PenguranganStok;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class KurangiStokAction
{
    /**
     * @param  list<array{kode_menu: string, jumlah: int}>  $items
     * @return array{kode_pesanan: string, status: string}
     */
    public function handle(string $kodePesanan, array $items): array
    {
        $itemsTerurut = collect($items)
            ->map(fn (array $item): array => [
                'kode_menu' => $item['kode_menu'],
                'jumlah' => (int) $item['jumlah'],
            ])
            ->sortBy('kode_menu')
            ->values()
            ->all();

        $payloadHash = hash(
            'sha256',
            json_encode($itemsTerurut, JSON_THROW_ON_ERROR),
        );

        return DB::transaction(function () use (
            $kodePesanan,
            $itemsTerurut,
            $payloadHash,
        ): array {
            $penguranganSebelumnya = PenguranganStok::query()
                ->where('kode_pesanan', $kodePesanan)
                ->lockForUpdate()
                ->first();

            if ($penguranganSebelumnya !== null) {
                if ($penguranganSebelumnya->payload_hash !== $payloadHash) {
                    throw new KonflikIdempotensiException($kodePesanan);
                }

                return [
                    'kode_pesanan' => $kodePesanan,
                    'status' => 'stok_dikurangi',
                ];
            }

            $menus = Menu::query()
                ->whereIn(
                    'kode_menu',
                    collect($itemsTerurut)->pluck('kode_menu'),
                )
                ->orderBy('kode_menu')
                ->lockForUpdate()
                ->get()
                ->keyBy('kode_menu');

            foreach ($itemsTerurut as $item) {
                $menu = $menus->get($item['kode_menu']);

                if ($menu === null) {
                    throw (new ModelNotFoundException)->setModel(
                        Menu::class,
                        [$item['kode_menu']],
                    );
                }

                if ($menu->stok < $item['jumlah']) {
                    throw new StokTidakMencukupiException(
                        $item['kode_menu'],
                    );
                }
            }

            foreach ($itemsTerurut as $item) {
                $menus->get($item['kode_menu'])->decrement(
                    'stok',
                    $item['jumlah'],
                );
            }

            PenguranganStok::query()->create([
                'kode_pesanan' => $kodePesanan,
                'payload_hash' => $payloadHash,
            ]);

            return [
                'kode_pesanan' => $kodePesanan,
                'status' => 'stok_dikurangi',
            ];
        });
    }
}
