<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PesananResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kode_pesanan' => $this->kode_pesanan,
            'kanal' => $this->kanal,
            'nama_pelanggan' => $this->nama_pelanggan,
            'status' => $this->status,
            'total' => $this->total,
            'items' => PesananItemResource::collection(
                $this->whenLoaded('items'),
            ),
        ];
    }
}
