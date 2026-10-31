<?php

namespace Tests\Feature\Api\V1\Pesanan;

use App\Messaging\PesananDikonfirmasiPublisher;
use App\Models\Pesanan;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class KegagalanIntegrasiKonfirmasiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_stok_ditolak_inventori_returns_problem_details_409(): void
    {
        $this->buatPesananMenunggu();

        Http::fake([
            $this->inventoriUrl() => Http::response([
                'type' => 'about:blank',
                'title' => 'Stok tidak mencukupi',
                'status' => 409,
                'detail' => 'Stok menu MNU-001 tidak mencukupi.',
                'instance' => '/api/v1/stok/kurangi',
            ], 409, [
                'Content-Type' => 'application/problem+json',
            ]),
        ]);

        $this->mock(
            PesananDikonfirmasiPublisher::class,
            function (MockInterface $mock): void {
                $mock->shouldNotReceive('publish');
            },
        );

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
            ->assertJsonPath('title', 'Stok tidak mencukupi')
            ->assertJsonPath('status', 409)
            ->assertJsonPath(
                'instance',
                '/api/v1/pesanan/PSN-000001/konfirmasi',
            );

        $this->assertDatabaseHas('pesanans', [
            'kode_pesanan' => 'PSN-000001',
            'status' => 'menunggu',
        ]);
    }

    public function test_gagal_menerbitkan_event_merollback_status_pesanan(): void
    {
        $this->buatPesananMenunggu();

        Http::fake([
            $this->inventoriUrl() => Http::response([
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
                    ->andThrow(
                        new RuntimeException(
                            'Simulasi RabbitMQ tidak tersedia.',
                        ),
                    );
            },
        );

        $response = $this->postJson(
            '/api/v1/pesanan/PSN-000001/konfirmasi',
        );

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
            ->assertJsonPath('title', 'Kesalahan internal')
            ->assertJsonPath('status', 500)
            ->assertJsonPath(
                'instance',
                '/api/v1/pesanan/PSN-000001/konfirmasi',
            );

        $this->assertDatabaseHas('pesanans', [
            'kode_pesanan' => 'PSN-000001',
            'status' => 'menunggu',
        ]);
    }

    private function buatPesananMenunggu(): void
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
    }

    private function inventoriUrl(): string
    {
        return rtrim(
            (string) config('services.inventori.base_url'),
            '/',
        ).'/api/v1/stok/kurangi';
    }
}
