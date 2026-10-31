<?php

namespace Tests\Feature\Api\V1\Operasional;

use App\Models\PesananOperasional;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PerbaruiStatusOperasionalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_status_dapat_diperbarui_ke_tahap_berikutnya(): void
    {
        $this->buatPesanan();

        $response = $this->patchJson(
            '/api/v1/pesanan/PSN-000001/status',
            [
                'status_operasional' => 'sedang_diproses',
            ],
        );

        $response
            ->assertOk()
            ->assertHeaderContains('Content-Type', 'application/json')
            ->assertJsonPath('data.kode_pesanan', 'PSN-000001')
            ->assertJsonPath(
                'data.status_operasional',
                'sedang_diproses',
            );

        $this->assertDatabaseHas('pesanan_operasionals', [
            'kode_pesanan' => 'PSN-000001',
            'status_operasional' => 'sedang_diproses',
        ]);
    }

    public function test_status_tidak_dapat_melompati_tahap(): void
    {
        $this->buatPesanan();

        $response = $this->patchJson(
            '/api/v1/pesanan/PSN-000001/status',
            [
                'status_operasional' => 'siap_diambil',
            ],
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
                '/api/v1/pesanan/PSN-000001/status',
            );

        $this->assertDatabaseHas('pesanan_operasionals', [
            'kode_pesanan' => 'PSN-000001',
            'status_operasional' => 'menunggu_diproses',
        ]);
    }

    public function test_payload_kosong_returns_problem_details_422(): void
    {
        $this->buatPesanan();

        $response = $this->patchJson(
            '/api/v1/pesanan/PSN-000001/status',
            [],
        );

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
            ->assertJsonPath('status', 422)
            ->assertJsonPath(
                'instance',
                '/api/v1/pesanan/PSN-000001/status',
            );
    }

    public function test_pesanan_yang_tidak_ada_returns_problem_details_404(): void
    {
        $response = $this->patchJson(
            '/api/v1/pesanan/PSN-999999/status',
            [
                'status_operasional' => 'sedang_diproses',
            ],
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
                '/api/v1/pesanan/PSN-999999/status',
            );
    }

    private function buatPesanan(): PesananOperasional
    {
        return PesananOperasional::query()->create([
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
    }
}
