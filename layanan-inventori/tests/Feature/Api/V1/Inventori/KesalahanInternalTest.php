<?php

namespace Tests\Feature\Api\V1\Inventori;

use App\Actions\Inventori\KurangiStokAction;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class KesalahanInternalTest extends TestCase
{
    public function test_unexpected_error_returns_problem_details_500(): void
    {
        $this->mock(
            KurangiStokAction::class,
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

        $response = $this->postJson('/api/v1/stok/kurangi', [
            'kode_pesanan' => 'PSN-000001',
            'items' => [
                [
                    'kode_menu' => 'MNU-001',
                    'jumlah' => 1,
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
            ->assertJsonPath('title', 'Kesalahan internal')
            ->assertJsonPath('status', 500)
            ->assertJsonPath('instance', '/api/v1/stok/kurangi');
    }
}
