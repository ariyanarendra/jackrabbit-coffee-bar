<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'kode_menu',
        'nama_menu',
        'harga_satuan',
        'stok',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harga_satuan' => 'integer',
            'stok' => 'integer',
        ];
    }
}
