<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Operasional\PerbaruiStatusOperasionalAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Operasional\PerbaruiStatusOperasionalRequest;
use App\Http\Resources\Api\V1\PesananOperasionalResource;
use App\Http\Resources\Api\V1\RingkasanPesananOperasionalResource;
use App\Models\PesananOperasional;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OperasionalController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = PesananOperasional::query()
            ->latest('received_at');

        $statusOperasional = $request
            ->string('status_operasional')
            ->toString();

        if ($statusOperasional !== '') {
            $query->where(
                'status_operasional',
                $statusOperasional,
            );
        }

        return RingkasanPesananOperasionalResource::collection(
            $query->get(),
        );
    }

    public function show(string $kode): PesananOperasionalResource
    {
        $pesanan = PesananOperasional::query()
            ->where('kode_pesanan', $kode)
            ->firstOrFail();

        return new PesananOperasionalResource($pesanan);
    }

    public function updateStatus(
        string $kode,
        PerbaruiStatusOperasionalRequest $request,
        PerbaruiStatusOperasionalAction $action,
    ): PesananOperasionalResource {
        $pesanan = $action->handle(
            kode: $kode,
            statusTujuan: $request->validated('status_operasional'),
        );

        return new PesananOperasionalResource($pesanan);
    }
}
