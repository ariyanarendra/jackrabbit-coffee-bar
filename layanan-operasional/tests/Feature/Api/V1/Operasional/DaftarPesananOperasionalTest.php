<?php

namespace Tests\Feature\Api\V1\Operasional;

use App\Models\PesananOperasional;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DaftarPesananOperasionalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_daftar_pesanan_operasional_returns_200(): void
    {
        PesananOperasional::query()->create([
            'event_id' => '9bdb7771-c4c5-4eca-ab7d-4420be544229',
            'kode_pesanan' => 'PSN-000001',
            'kanal' => 'grabfood',
            'nama_pelanggan' => 'Andi',
            'status_pesanan' => 'dikonfirmasi',
            'status_operasional' => 'menunggu_diproses',
            'total' => 50000,
            'payload' => $this->payload(
                kodePesanan: 'PSN-000001',
                statusOperasional: 'menunggu_diproses',
            ),
            'occurred_at' => '2026-10-31 03:18:12',
            'received_at' => '2026-10-31 03:18:13',
        ]);

        $response = $this->getJson('/api/v1/pesanan');

        $response
            ->assertOk()
            ->assertHeaderContains('Content-Type', 'application/json')
            ->assertExactJson([
                'data' => [
                    [
                        'kode_pesanan' => 'PSN-000001',
                        'kanal' => 'grabfood',
                        'nama_pelanggan' => 'Andi',
                        'status_pesanan' => 'dikonfirmasi',
                        'status_operasional' => 'menunggu_diproses',
                        'total' => 50000,
                        'occurred_at' => '2026-10-31T03:18:12+00:00',
                        'received_at' => '2026-10-31T03:18:13+00:00',
                    ],
                ],
            ]);
    }

    public function test_daftar_dapat_disaring_berdasarkan_status_operasional(): void
    {
        PesananOperasional::query()->create([
            'event_id' => '11111111-1111-4111-8111-111111111111',
            'kode_pesanan' => 'PSN-000001',
            'kanal' => 'grabfood',
            'nama_pelanggan' => 'Andi',
            'status_pesanan' => 'dikonfirmasi',
            'status_operasional' => 'menunggu_diproses',
            'total' => 50000,
            'payload' => $this->payload(
                kodePesanan: 'PSN-000001',
                statusOperasional: 'menunggu_diproses',
            ),
            'occurred_at' => '2026-10-31 03:18:12',
            'received_at' => '2026-10-31 03:18:13',
        ]);

        PesananOperasional::query()->create([
            'event_id' => '22222222-2222-4222-8222-222222222222',
            'kode_pesanan' => 'PSN-000002',
            'kanal' => 'gofood',
            'nama_pelanggan' => 'Budi',
            'status_pesanan' => 'dikonfirmasi',
            'status_operasional' => 'selesai',
            'total' => 25000,
            'payload' => $this->payload(
                kodePesanan: 'PSN-000002',
                statusOperasional: 'selesai',
            ),
            'occurred_at' => '2026-10-31 03:20:12',
            'received_at' => '2026-10-31 03:20:13',
        ]);

        $response = $this->getJson(
            '/api/v1/pesanan?status_operasional=selesai',
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kode_pesanan', 'PSN-000002')
            ->assertJsonPath('data.0.status_operasional', 'selesai');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(
        string $kodePesanan,
        string $statusOperasional,
    ): array {
        return [
            'event_id' => '9bdb7771-c4c5-4eca-ab7d-4420be544229',
            'event_name' => 'PesananDikonfirmasi',
            'occurred_at' => '2026-10-31T03:18:12+00:00',
            'data' => [
                'kode_pesanan' => $kodePesanan,
                'kanal' => 'grabfood',
                'nama_pelanggan' => 'Andi',
                'status' => 'dikonfirmasi',
                'status_operasional' => $statusOperasional,
                'total' => 50000,
                'items' => [
                    [
                        'kode_menu' => 'MNU-001',
                        'nama_menu' => 'Cafe Latte',
                        'jumlah' => 2,
                        'harga_satuan' => 25000,
                        'subtotal' => 50000,
                    ],
                ],
            ],
        ];
    }
}
