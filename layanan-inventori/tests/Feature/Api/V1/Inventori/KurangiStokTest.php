<?php

namespace Tests\Feature\Api\V1\Inventori;

use App\Models\Menu;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class KurangiStokTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_stok_mencukupi_dapat_dikurangi_secara_atomik(): void
    {
        Menu::query()->create([
            'kode_menu' => 'MNU-001',
            'nama_menu' => 'Cafe Latte',
            'harga_satuan' => 25000,
            'stok' => 20,
        ]);

        Menu::query()->create([
            'kode_menu' => 'MNU-002',
            'nama_menu' => 'Americano',
            'harga_satuan' => 20000,
            'stok' => 10,
        ]);

        $response = $this->postJson('/api/v1/stok/kurangi', [
            'kode_pesanan' => 'PSN-000001',
            'items' => [
                [
                    'kode_menu' => 'MNU-001',
                    'jumlah' => 2,
                ],
                [
                    'kode_menu' => 'MNU-002',
                    'jumlah' => 1,
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertHeaderContains('Content-Type', 'application/json')
            ->assertExactJson([
                'data' => [
                    'kode_pesanan' => 'PSN-000001',
                    'status' => 'stok_dikurangi',
                ],
            ]);

        $this->assertDatabaseHas('menus', [
            'kode_menu' => 'MNU-001',
            'stok' => 18,
        ]);

        $this->assertDatabaseHas('menus', [
            'kode_menu' => 'MNU-002',
            'stok' => 9,
        ]);

        $this->assertDatabaseHas('pengurangan_stoks', [
            'kode_pesanan' => 'PSN-000001',
        ]);
    }

    public function test_request_yang_sama_bersifat_idempoten(): void
    {
        Menu::query()->create([
            'kode_menu' => 'MNU-001',
            'nama_menu' => 'Cafe Latte',
            'harga_satuan' => 25000,
            'stok' => 10,
        ]);

        $payload = [
            'kode_pesanan' => 'PSN-000001',
            'items' => [
                [
                    'kode_menu' => 'MNU-001',
                    'jumlah' => 2,
                ],
            ],
        ];

        $this->postJson('/api/v1/stok/kurangi', $payload)
            ->assertOk();

        $this->postJson('/api/v1/stok/kurangi', $payload)
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'kode_pesanan' => 'PSN-000001',
                    'status' => 'stok_dikurangi',
                ],
            ]);

        $this->assertDatabaseHas('menus', [
            'kode_menu' => 'MNU-001',
            'stok' => 8,
        ]);

        $this->assertDatabaseCount('pengurangan_stoks', 1);
    }

    public function test_kode_pesanan_yang_sama_dengan_payload_berbeda_returns_409(): void
    {
        Menu::query()->create([
            'kode_menu' => 'MNU-001',
            'nama_menu' => 'Cafe Latte',
            'harga_satuan' => 25000,
            'stok' => 10,
        ]);

        $this->postJson('/api/v1/stok/kurangi', [
            'kode_pesanan' => 'PSN-000001',
            'items' => [
                [
                    'kode_menu' => 'MNU-001',
                    'jumlah' => 2,
                ],
            ],
        ])->assertOk();

        $response = $this->postJson('/api/v1/stok/kurangi', [
            'kode_pesanan' => 'PSN-000001',
            'items' => [
                [
                    'kode_menu' => 'MNU-001',
                    'jumlah' => 3,
                ],
            ],
        ]);

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
            ->assertJsonPath('title', 'Konflik idempotensi')
            ->assertJsonPath('status', 409)
            ->assertJsonPath('instance', '/api/v1/stok/kurangi');

        $this->assertDatabaseHas('menus', [
            'kode_menu' => 'MNU-001',
            'stok' => 8,
        ]);

        $this->assertDatabaseCount('pengurangan_stoks', 1);
    }

    public function test_stok_tidak_mencukupi_returns_problem_details_409(): void
    {
        Menu::query()->create([
            'kode_menu' => 'MNU-001',
            'nama_menu' => 'Cafe Latte',
            'harga_satuan' => 25000,
            'stok' => 1,
        ]);

        $response = $this->postJson('/api/v1/stok/kurangi', [
            'kode_pesanan' => 'PSN-000001',
            'items' => [
                [
                    'kode_menu' => 'MNU-001',
                    'jumlah' => 2,
                ],
            ],
        ]);

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
            ->assertJsonPath('instance', '/api/v1/stok/kurangi');

        $this->assertDatabaseHas('menus', [
            'kode_menu' => 'MNU-001',
            'stok' => 1,
        ]);

        $this->assertDatabaseCount('pengurangan_stoks', 0);
    }

    public function test_menu_yang_tidak_ada_returns_problem_details_404(): void
    {
        $response = $this->postJson('/api/v1/stok/kurangi', [
            'kode_pesanan' => 'PSN-000001',
            'items' => [
                [
                    'kode_menu' => 'MNU-999',
                    'jumlah' => 1,
                ],
            ],
        ]);

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
            ->assertJsonPath('instance', '/api/v1/stok/kurangi');

        $this->assertDatabaseCount('pengurangan_stoks', 0);
    }

    public function test_payload_kosong_returns_problem_details_422(): void
    {
        $response = $this->postJson('/api/v1/stok/kurangi', []);

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
            ->assertJsonPath('instance', '/api/v1/stok/kurangi');
    }
}
