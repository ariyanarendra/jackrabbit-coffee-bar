<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesananOperasional extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'kode_pesanan',
        'kanal',
        'nama_pelanggan',
        'status_pesanan',
        'status_operasional',
        'total',
        'payload',
        'occurred_at',
        'received_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'payload' => 'array',
            'occurred_at' => 'immutable_datetime',
            'received_at' => 'immutable_datetime',
        ];
    }
}
