<?php

namespace App\Actions\Inventori;

use App\Models\Menu;

class CekStokAction
{
    /**
     * @param  array<int, array{kode_menu: string, jumlah: int}>  $items
     * @return array{
     *     tersedia: bool,
     *     items: array<int, array{
     *         kode_menu: string,
     *         jumlah_diminta: int,
     *         stok_tersedia: int,
     *         tersedia: bool
     *     }>
     * }
     */
    public function handle(array $items): array
    {
        $hasilItems = collect($items)
            ->map(function (array $item): array {
                $menu = Menu::query()
                    ->where('kode_menu', $item['kode_menu'])
                    ->firstOrFail();

                $jumlah = (int) $item['jumlah'];
                $tersedia = $menu->stok >= $jumlah;

                return [
                    'kode_menu' => $menu->kode_menu,
                    'jumlah_diminta' => $jumlah,
                    'stok_tersedia' => $menu->stok,
                    'tersedia' => $tersedia,
                ];
            })
            ->all();

        return [
            'tersedia' => collect($hasilItems)
                ->every(
                    fn (array $item): bool => $item['tersedia'],
                ),
            'items' => $hasilItems,
        ];
    }
}
