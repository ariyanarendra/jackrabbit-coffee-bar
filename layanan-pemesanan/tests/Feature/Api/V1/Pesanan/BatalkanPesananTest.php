<?php

namespace Tests\Feature\Api\V1\Pesanan;

use App\Models\Pesanan;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BatalkanPesananTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pesanan_menunggu_dapat_dibatalkan(): void
    {
        Pesanan::query()->create([
            'kode_pesanan' => 'PSN-000001',
            'kanal' => 'grabfood',
            'nama_pelanggan' => 'Andi',
            'status' => 'menunggu',
            'total' => 50000,
        ]);

        $response = $this->postJson(
            '/api/v1/pesanan/PSN-000001/batal',
        );

        $response
            ->assertOk()
            ->assertHeaderContains('Content-Type', 'application/json')
            ->assertExactJson([
                'data' => [
                    'kode_pesanan' => 'PSN-000001',
                    'status' => 'dibatalkan',
                ],
            ]);

        $this->assertDatabaseHas('pesanans', [
            'kode_pesanan' => 'PSN-000001',
            'status' => 'dibatalkan',
        ]);
    }

    public function test_pesanan_dikonfirmasi_tidak_dapat_dibatalkan(): void
    {
        Pesanan::query()->create([
            'kode_pesanan' => 'PSN-000001',
            'kanal' => 'grabfood',
            'nama_pelanggan' => 'Andi',
            'status' => 'dikonfirmasi',
            'total' => 50000,
        ]);

        $response = $this->postJson(
            '/api/v1/pesanan/PSN-000001/batal',
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
                '/api/v1/pesanan/PSN-000001/batal',
            );

        $this->assertDatabaseHas('pesanans', [
            'kode_pesanan' => 'PSN-000001',
            'status' => 'dikonfirmasi',
        ]);
    }

    public function test_pesanan_yang_tidak_ada_returns_problem_details_404(): void
    {
        $response = $this->postJson(
            '/api/v1/pesanan/PSN-999999/batal',
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
                '/api/v1/pesanan/PSN-999999/batal',
            );
    }
}
