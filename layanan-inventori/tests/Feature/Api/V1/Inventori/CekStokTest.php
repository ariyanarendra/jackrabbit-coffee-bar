<?php

namespace Tests\Feature\Api\V1\Inventori;

use App\Models\Menu;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CekStokTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_semua_stok_mencukupi_returns_tersedia_true(): void
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

        $response = $this->postJson('/api/v1/stok/cek', [
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
                    'tersedia' => true,
                    'items' => [
                        [
                            'kode_menu' => 'MNU-001',
                            'jumlah_diminta' => 2,
                            'stok_tersedia' => 20,
                            'tersedia' => true,
                        ],
                        [
                            'kode_menu' => 'MNU-002',
                            'jumlah_diminta' => 1,
                            'stok_tersedia' => 10,
                            'tersedia' => true,
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('menus', [
            'kode_menu' => 'MNU-001',
            'stok' => 20,
        ]);

        $this->assertDatabaseHas('menus', [
            'kode_menu' => 'MNU-002',
            'stok' => 10,
        ]);
    }

    public function test_stok_tidak_mencukupi_returns_tersedia_false(): void
    {
        Menu::query()->create([
            'kode_menu' => 'MNU-001',
            'nama_menu' => 'Cafe Latte',
            'harga_satuan' => 25000,
            'stok' => 1,
        ]);

        $response = $this->postJson('/api/v1/stok/cek', [
            'items' => [
                [
                    'kode_menu' => 'MNU-001',
                    'jumlah' => 2,
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'tersedia' => false,
                    'items' => [
                        [
                            'kode_menu' => 'MNU-001',
                            'jumlah_diminta' => 2,
                            'stok_tersedia' => 1,
                            'tersedia' => false,
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('menus', [
            'kode_menu' => 'MNU-001',
            'stok' => 1,
        ]);
    }

    public function test_menu_yang_tidak_ada_returns_problem_details_404(): void
    {
        $response = $this->postJson('/api/v1/stok/cek', [
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
            ->assertJsonPath('instance', '/api/v1/stok/cek');
    }

    public function test_payload_kosong_returns_problem_details_422(): void
    {
        $response = $this->postJson('/api/v1/stok/cek', []);

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
            ->assertJsonPath('instance', '/api/v1/stok/cek');
    }
}
