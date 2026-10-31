<?php

namespace Tests\Feature\Messaging;

use App\Actions\TanganiPesananDikonfirmasiAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TanganiPesananDikonfirmasiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_event_disimpan_sebagai_pesanan_operasional(): void
    {
        $pesanan = app(TanganiPesananDikonfirmasiAction::class)
            ->handle($this->event());

        $this->assertSame(
            '9bdb7771-c4c5-4eca-ab7d-4420be544229',
            $pesanan->event_id,
        );
        $this->assertSame('PSN-E2E-0001', $pesanan->kode_pesanan);
        $this->assertSame('uji-integrasi', $pesanan->kanal);
        $this->assertSame('Pelanggan Uji', $pesanan->nama_pelanggan);
        $this->assertSame('dikonfirmasi', $pesanan->status_pesanan);
        $this->assertSame(
            'menunggu_diproses',
            $pesanan->status_operasional,
        );
        $this->assertSame(50000, $pesanan->total);
        $this->assertSame(
            'MNU-E2E-001',
            $pesanan->payload['data']['items'][0]['kode_menu'],
        );

        $this->assertDatabaseHas('pesanan_operasionals', [
            'event_id' => '9bdb7771-c4c5-4eca-ab7d-4420be544229',
            'kode_pesanan' => 'PSN-E2E-0001',
            'status_pesanan' => 'dikonfirmasi',
            'status_operasional' => 'menunggu_diproses',
            'total' => 50000,
        ]);
    }

    public function test_event_yang_sama_bersifat_idempoten(): void
    {
        $action = app(TanganiPesananDikonfirmasiAction::class);

        $pertama = $action->handle($this->event());
        $kedua = $action->handle($this->event());

        $this->assertSame($pertama->id, $kedua->id);
        $this->assertDatabaseCount('pesanan_operasionals', 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function event(): array
    {
        return [
            'event_id' => '9bdb7771-c4c5-4eca-ab7d-4420be544229',
            'event_name' => 'PesananDikonfirmasi',
            'occurred_at' => '2026-10-31T03:18:12+00:00',
            'data' => [
                'kode_pesanan' => 'PSN-E2E-0001',
                'kanal' => 'uji-integrasi',
                'nama_pelanggan' => 'Pelanggan Uji',
                'status' => 'dikonfirmasi',
                'total' => 50000,
                'items' => [
                    [
                        'kode_menu' => 'MNU-E2E-001',
                        'nama_menu' => 'Menu Uji Integrasi',
                        'jumlah' => 2,
                        'harga_satuan' => 25000,
                        'subtotal' => 50000,
                    ],
                ],
            ],
        ];
    }
}
