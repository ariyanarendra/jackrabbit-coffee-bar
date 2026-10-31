<?php

namespace Tests\Feature\Api\V1\Pesanan;

use App\Actions\Pesanan\BuatPesananAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class BuatPesananTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_payload_valid_creates_pesanan_and_returns_201(): void
    {
        $response = $this->postJson('/api/v1/pesanan', [
            'kanal' => 'grabfood',
            'nama_pelanggan' => 'Andi',
            'items' => [
                [
                    'kode_menu' => 'MNU-001',
                    'nama_menu' => 'Cafe Latte',
                    'jumlah' => 2,
                    'harga_satuan' => 25000,
                ],
                [
                    'kode_menu' => 'MNU-002',
                    'nama_menu' => 'Americano',
                    'jumlah' => 1,
                    'harga_satuan' => 20000,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertHeader('Location')
            ->assertHeaderContains('Content-Type', 'application/json')
            ->assertJsonStructure([
                'data' => [
                    'kode_pesanan',
                    'kanal',
                    'nama_pelanggan',
                    'status',
                    'total',
                    'items' => [
                        '*' => [
                            'kode_menu',
                            'nama_menu',
                            'jumlah',
                            'harga_satuan',
                            'subtotal',
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'kanal' => 'grabfood',
                    'nama_pelanggan' => 'Andi',
                    'status' => 'menunggu',
                    'total' => 70000,
                ],
            ])
            ->assertJsonCount(2, 'data.items')
            ->assertJsonFragment([
                'kode_menu' => 'MNU-001',
                'nama_menu' => 'Cafe Latte',
                'jumlah' => 2,
                'harga_satuan' => 25000,
                'subtotal' => 50000,
            ])
            ->assertJsonFragment([
                'kode_menu' => 'MNU-002',
                'nama_menu' => 'Americano',
                'jumlah' => 1,
                'harga_satuan' => 20000,
                'subtotal' => 20000,
            ]);

        $kodePesanan = $response->json('data.kode_pesanan');

        $this->assertIsString($kodePesanan);
        $this->assertNotSame('', $kodePesanan);
        $response->assertLocation("/api/v1/pesanan/{$kodePesanan}");

        $this->assertDatabaseCount('pesanans', 1);
        $this->assertDatabaseHas('pesanans', [
            'kode_pesanan' => $kodePesanan,
            'kanal' => 'grabfood',
            'nama_pelanggan' => 'Andi',
            'status' => 'menunggu',
            'total' => 70000,
        ]);

        $this->assertDatabaseCount('pesanan_items', 2);
        $this->assertDatabaseHas('pesanan_items', [
            'kode_menu' => 'MNU-001',
            'nama_menu' => 'Cafe Latte',
            'jumlah' => 2,
            'harga_satuan' => 25000,
            'subtotal' => 50000,
        ]);
        $this->assertDatabaseHas('pesanan_items', [
            'kode_menu' => 'MNU-002',
            'nama_menu' => 'Americano',
            'jumlah' => 1,
            'harga_satuan' => 20000,
            'subtotal' => 20000,
        ]);
    }

    public function test_payload_kosong_returns_problem_details_422(): void
    {
        $response = $this->postJson('/api/v1/pesanan', []);

        $response
            ->assertUnprocessable()
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
            ->assertJsonPath('status', 422);

        $this->assertDatabaseCount('pesanans', 0);
    }

    public function test_unexpected_error_returns_problem_details_500(): void
    {
        $this->mock(
            BuatPesananAction::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('handle')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Simulasi kesalahan internal.',
                        ),
                    );
            },
        );

        $response = $this->postJson('/api/v1/pesanan', [
            'kanal' => 'grabfood',
            'nama_pelanggan' => 'Andi',
            'items' => [
                [
                    'kode_menu' => 'MNU-001',
                    'nama_menu' => 'Cafe Latte',
                    'jumlah' => 1,
                    'harga_satuan' => 25000,
                ],
            ],
        ]);

        $response
            ->assertInternalServerError()
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
            ->assertJsonPath('status', 500)
            ->assertJsonPath('instance', '/api/v1/pesanan');

        $this->assertDatabaseCount('pesanans', 0);
    }
}
