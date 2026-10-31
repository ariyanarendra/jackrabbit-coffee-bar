<?php

namespace App\Integrations\Inventori;

use App\Exceptions\StokInventoriTidakTersediaException;
use App\Models\Pesanan;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class InventoriClient
{
    public function kurangiStok(Pesanan $pesanan): void
    {
        $baseUrl = rtrim(
            (string) config('services.inventori.base_url'),
            '/',
        );

        $response = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout(5)
            ->post('/api/v1/stok/kurangi', [
                'kode_pesanan' => $pesanan->kode_pesanan,
                'items' => $pesanan->items
                    ->map(fn ($item): array => [
                        'kode_menu' => $item->kode_menu,
                        'jumlah' => $item->jumlah,
                    ])
                    ->values()
                    ->all(),
            ]);

        if (in_array($response->status(), [404, 409], true)) {
            $detail = $response->json('detail');

            if (is_string($detail)) {
                throw new StokInventoriTidakTersediaException($detail);
            }

            throw new StokInventoriTidakTersediaException;
        }

        $response->throw();

        if (
            $response->json('data.kode_pesanan') !== $pesanan->kode_pesanan
            || $response->json('data.status') !== 'stok_dikurangi'
        ) {
            throw new UnexpectedValueException(
                'Respons layanan Inventori tidak sesuai kontrak.',
            );
        }
    }
}
