<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenguranganStok extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'kode_pesanan',
        'payload_hash',
    ];
}
