<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Inventori\CekStokAction;
use App\Actions\Inventori\KurangiStokAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Inventori\CekStokRequest;
use App\Http\Requests\Api\V1\Inventori\KurangiStokRequest;
use App\Http\Resources\Api\V1\MenuResource;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;

class InventoriController extends Controller
{
    public function show(string $kode): MenuResource
    {
        $menu = Menu::query()
            ->where('kode_menu', $kode)
            ->firstOrFail();

        return new MenuResource($menu);
    }

    public function cekStok(
        CekStokRequest $request,
        CekStokAction $action,
    ): JsonResponse {
        $validated = $request->validated();

        return response()->json([
            'data' => $action->handle($validated['items']),
        ]);
    }

    public function kurangiStok(
        KurangiStokRequest $request,
        KurangiStokAction $action,
    ): JsonResponse {
        $validated = $request->validated();

        return response()->json([
            'data' => $action->handle(
                $validated['kode_pesanan'],
                $validated['items'],
            ),
        ]);
    }
}
