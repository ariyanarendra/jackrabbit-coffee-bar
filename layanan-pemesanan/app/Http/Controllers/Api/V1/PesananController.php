<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Pesanan\BatalkanPesananAction;
use App\Actions\Pesanan\BuatPesananAction;
use App\Actions\Pesanan\KonfirmasiPesananAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Pesanan\BuatPesananRequest;
use App\Http\Resources\Api\V1\PesananDibatalkanResource;
use App\Http\Resources\Api\V1\PesananDikonfirmasiResource;
use App\Http\Resources\Api\V1\PesananResource;
use App\Models\Pesanan;
use Illuminate\Http\JsonResponse;

class PesananController extends Controller
{
    public function store(
        BuatPesananRequest $request,
        BuatPesananAction $buatPesanan,
    ): JsonResponse {
        $pesanan = $buatPesanan->handle($request->validated());

        $response = (new PesananResource($pesanan))->response();
        $response->setStatusCode(201);
        $response->headers->set(
            'Location',
            "/api/v1/pesanan/{$pesanan->kode_pesanan}",
        );

        return $response;
    }

    public function show(string $kode): PesananResource
    {
        $pesanan = Pesanan::query()
            ->where('kode_pesanan', $kode)
            ->with('items')
            ->firstOrFail();

        return new PesananResource($pesanan);
    }

    public function konfirmasi(
        string $kode,
        KonfirmasiPesananAction $konfirmasiPesanan,
    ): PesananDikonfirmasiResource {
        $pesanan = $konfirmasiPesanan->handle($kode);

        return new PesananDikonfirmasiResource($pesanan);
    }

    public function batal(
        string $kode,
        BatalkanPesananAction $batalkanPesanan,
    ): PesananDibatalkanResource {
        $pesanan = $batalkanPesanan->handle($kode);

        return new PesananDibatalkanResource($pesanan);
    }
}
