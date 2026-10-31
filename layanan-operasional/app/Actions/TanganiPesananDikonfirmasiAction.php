<?php

namespace App\Actions;

use App\Models\PesananOperasional;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TanganiPesananDikonfirmasiAction
{
    /**
     * @param  array<string, mixed>  $event
     */
    public function handle(array $event): PesananOperasional
    {
        $validated = Validator::make($event, [
            'event_id' => ['required', 'uuid'],
            'event_name' => [
                'required',
                'string',
                'in:PesananDikonfirmasi',
            ],
            'occurred_at' => ['required', 'date'],
            'data' => ['required', 'array'],
            'data.kode_pesanan' => ['required', 'string'],
            'data.kanal' => ['required', 'string'],
            'data.nama_pelanggan' => ['required', 'string'],
            'data.status' => [
                'required',
                'string',
                'in:dikonfirmasi',
            ],
            'data.total' => ['required', 'integer', 'min:0'],
            'data.items' => ['required', 'array', 'min:1'],
            'data.items.*.kode_menu' => ['required', 'string'],
            'data.items.*.nama_menu' => ['required', 'string'],
            'data.items.*.jumlah' => [
                'required',
                'integer',
                'min:1',
            ],
            'data.items.*.harga_satuan' => [
                'required',
                'integer',
                'min:0',
            ],
            'data.items.*.subtotal' => [
                'required',
                'integer',
                'min:0',
            ],
        ])->validate();

        return DB::transaction(function () use (
            $validated,
        ): PesananOperasional {
            $pesanan = PesananOperasional::query()
                ->where('event_id', $validated['event_id'])
                ->orWhere(
                    'kode_pesanan',
                    $validated['data']['kode_pesanan'],
                )
                ->lockForUpdate()
                ->first();

            if ($pesanan !== null) {
                return $pesanan;
            }

            return PesananOperasional::query()->create([
                'event_id' => $validated['event_id'],
                'kode_pesanan' => $validated['data']['kode_pesanan'],
                'kanal' => $validated['data']['kanal'],
                'nama_pelanggan' => $validated['data']['nama_pelanggan'],
                'status_pesanan' => $validated['data']['status'],
                'status_operasional' => 'menunggu_diproses',
                'total' => $validated['data']['total'],
                'payload' => $validated,
                'occurred_at' => CarbonImmutable::parse(
                    $validated['occurred_at'],
                ),
                'received_at' => now(),
            ]);
        });
    }
}
