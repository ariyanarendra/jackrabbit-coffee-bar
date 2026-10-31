<?php

namespace Tests\Feature\Api\V1\Inventori;

use App\Models\Menu;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LihatMenuTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_menu_yang_ada_returns_detail_200(): void
    {
        Menu::query()->create([
            'kode_menu' => 'MNU-001',
            'nama_menu' => 'Cafe Latte',
            'harga_satuan' => 25000,
            'stok' => 20,
        ]);

        $response = $this->getJson('/api/v1/menu/MNU-001');

        $response
            ->assertOk()
            ->assertHeaderContains('Content-Type', 'application/json')
            ->assertExactJson([
                'data' => [
                    'kode_menu' => 'MNU-001',
                    'nama_menu' => 'Cafe Latte',
                    'harga_satuan' => 25000,
                    'stok' => 20,
                ],
            ]);
    }

    public function test_menu_yang_tidak_ada_returns_problem_details_404(): void
    {
        $response = $this->getJson('/api/v1/menu/MNU-999');

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
            ->assertJsonPath('instance', '/api/v1/menu/MNU-999');
    }
}
