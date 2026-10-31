<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kode_menu' => $this->kode_menu,
            'nama_menu' => $this->nama_menu,
            'harga_satuan' => $this->harga_satuan,
            'stok' => $this->stok,
        ];
    }
}
