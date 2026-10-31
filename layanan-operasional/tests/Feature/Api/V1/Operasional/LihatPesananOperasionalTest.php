<?php

namespace Tests\Feature\Api\V1\Operasional;

use App\Models\PesananOperasional;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LihatPesananOperasionalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pesanan_yang_ada_returns_detail_200(): void
    {
        PesananOperasional::query()->create([
            'event_id' => '9bdb7771-c4c5-4eca-ab7d-4420be544229',
            'kode_pesanan' => 'PSN-000001',
            'kanal' => 'grabfood',
            'nama_pelanggan' => 'Andi',
            'status_pesanan' => 'dikonfirmasi',
            'status_operasional' => 'menunggu_diproses',
            'total' => 50000,
            'payload' => [
                'event_id' => '9bdb7771-c4c5-4eca-ab7d-4420be544229',
                'event_name' => 'PesananDikonfirmasi',
                'occurred_at' => '2026-10-31T03:18:12+00:00',
                'data' => [
                    'kode_pesanan' => 'PSN-000001',
                    'kanal' => 'grabfood',
                    'nama_pelanggan' => 'Andi',
                    'status' => 'dikonfirmasi',
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
            ],
            'occurred_at' => '2026-10-31 03:18:12',
            'received_at' => '2026-10-31 03:18:13',
        ]);

        $response = $this->getJson('/api/v1/pesanan/PSN-000001');

        $response
            ->assertOk()
            ->assertHeaderContains('Content-Type', 'application/json')
            ->assertExactJson([
                'data' => [
                    'kode_pesanan' => 'PSN-000001',
                    'kanal' => 'grabfood',
                    'nama_pelanggan' => 'Andi',
                    'status_pesanan' => 'dikonfirmasi',
                    'status_operasional' => 'menunggu_diproses',
                    'total' => 50000,
                    'occurred_at' => '2026-10-31T03:18:12+00:00',
                    'received_at' => '2026-10-31T03:18:13+00:00',
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
            ]);
    }

    public function test_pesanan_yang_tidak_ada_returns_problem_details_404(): void
    {
        $response = $this->getJson('/api/v1/pesanan/PSN-999999');

        $response
            ->assertNotFound()
            ->assertHeaderContains(
                'Content-Type',
                'application/problem+json',
            )
            ->assertJsonStructure([
                'type',
                'title',
                'status',
                'detail',
                'instance',
            ])
            ->assertJsonPath('status', 404)
            ->assertJsonPath(
                'instance',
                '/api/v1/pesanan/PSN-999999',
            );
    }
}
