<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RingkasanPesananOperasionalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kode_pesanan' => $this->kode_pesanan,
            'kanal' => $this->kanal,
            'nama_pelanggan' => $this->nama_pelanggan,
            'status_pesanan' => $this->status_pesanan,
            'status_operasional' => $this->status_operasional,
            'total' => $this->total,
            'occurred_at' => $this->occurred_at->toIso8601String(),
            'received_at' => $this->received_at->toIso8601String(),
        ];
    }
}
