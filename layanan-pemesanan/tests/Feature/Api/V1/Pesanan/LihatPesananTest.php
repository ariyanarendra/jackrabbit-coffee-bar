<?php

namespace Tests\Feature\Api\V1\Pesanan;

use App\Models\Pesanan;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LihatPesananTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pesanan_yang_ada_returns_detail_200(): void
    {
        $pesanan = Pesanan::query()->create([
            'kode_pesanan' => 'PSN-000001',
            'kanal' => 'grabfood',
            'nama_pelanggan' => 'Andi',
            'status' => 'menunggu',
            'total' => 50000,
        ]);

        $pesanan->items()->create([
            'kode_menu' => 'MNU-001',
            'nama_menu' => 'Cafe Latte',
            'jumlah' => 2,
            'harga_satuan' => 25000,
            'subtotal' => 50000,
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
                    'status' => 'menunggu',
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
