<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'kode_pesanan',
    'kanal',
    'nama_pelanggan',
    'status',
    'total',
])]
class Pesanan extends Model
{
    public function items(): HasMany
    {
        return $this->hasMany(PesananItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'integer',
        ];
    }
}
