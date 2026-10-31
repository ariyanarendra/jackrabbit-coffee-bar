<?php

namespace Tests\Feature\Api\V1\Pesanan;

use App\Messaging\PesananDikonfirmasiPublisher;
use App\Models\Pesanan;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Tests\TestCase;

class KonfirmasiPesananTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pesanan_menunggu_dapat_dikonfirmasi(): void
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

        $inventoriUrl = rtrim(
            (string) config('services.inventori.base_url'),
            '/',
        ).'/api/v1/stok/kurangi';

        Http::fake([
            $inventoriUrl => Http::response([
                'data' => [
                    'kode_pesanan' => 'PSN-000001',
                    'status' => 'stok_dikurangi',
                ],
            ]),
        ]);

        $this->mock(
            PesananDikonfirmasiPublisher::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('publish')
                    ->once()
                    ->withArgs(
                        fn (Pesanan $pesanan): bool => $pesanan->kode_pesanan === 'PSN-000001'
                            && $pesanan->status === 'dikonfirmasi'
                            && $pesanan->relationLoaded('items'),
                    );
            },
        );

        $response = $this->postJson(
            '/api/v1/pesanan/PSN-000001/konfirmasi',
        );

        $response
            ->assertOk()
            ->assertHeaderContains('Content-Type', 'application/json')
            ->assertExactJson([
                'data' => [
                    'kode_pesanan' => 'PSN-000001',
                    'status' => 'dikonfirmasi',
                ],
            ]);

        Http::assertSent(
            fn (Request $request): bool => $request->method() === 'POST'
                && $request->url() === $inventoriUrl
                && $request->data() === [
                    'kode_pesanan' => 'PSN-000001',
                    'items' => [
                        [
                            'kode_menu' => 'MNU-001',
                            'jumlah' => 2,
                        ],
                    ],
                ],
        );

        $this->assertDatabaseHas('pesanans', [
            'kode_pesanan' => 'PSN-000001',
            'status' => 'dikonfirmasi',
        ]);
    }

    public function test_pesanan_dibatalkan_tidak_dapat_dikonfirmasi(): void
    {
        Pesanan::query()->create([
            'kode_pesanan' => 'PSN-000001',
            'kanal' => 'grabfood',
            'nama_pelanggan' => 'Andi',
            'status' => 'dibatalkan',
            'total' => 50000,
        ]);

        $response = $this->postJson(
            '/api/v1/pesanan/PSN-000001/konfirmasi',
        );

        $response
            ->assertConflict()
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
            ->assertJsonPath('status', 409)
            ->assertJsonPath(
                'instance',
                '/api/v1/pesanan/PSN-000001/konfirmasi',
            );

        $this->assertDatabaseHas('pesanans', [
            'kode_pesanan' => 'PSN-000001',
            'status' => 'dibatalkan',
        ]);
    }

    public function test_pesanan_yang_tidak_ada_returns_problem_details_404(): void
    {
        $response = $this->postJson(
            '/api/v1/pesanan/PSN-999999/konfirmasi',
        );

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
                '/api/v1/pesanan/PSN-999999/konfirmasi',
            );
    }
}
