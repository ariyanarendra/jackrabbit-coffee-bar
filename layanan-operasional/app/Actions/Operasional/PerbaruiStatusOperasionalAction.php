<?php

namespace App\Actions\Operasional;

use App\Exceptions\PerubahanStatusOperasionalTidakValidException;
use App\Models\PesananOperasional;
use Illuminate\Support\Facades\DB;

class PerbaruiStatusOperasionalAction
{
    public function handle(
        string $kode,
        string $statusTujuan,
    ): PesananOperasional {
        return DB::transaction(function () use (
            $kode,
            $statusTujuan,
        ): PesananOperasional {
            $pesanan = PesananOperasional::query()
                ->where('kode_pesanan', $kode)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pesanan->status_operasional === $statusTujuan) {
                return $pesanan;
            }

            $urutanStatus = [
                'menunggu_diproses',
                'sedang_diproses',
                'siap_diambil',
                'selesai',
            ];

            $posisiSaatIni = array_search(
                $pesanan->status_operasional,
                $urutanStatus,
                true,
            );

            $posisiTujuan = array_search(
                $statusTujuan,
                $urutanStatus,
                true,
            );

            if (
                $posisiSaatIni === false
                || $posisiTujuan === false
                || $posisiTujuan !== $posisiSaatIni + 1
            ) {
                throw new PerubahanStatusOperasionalTidakValidException(
                    kodePesanan: $pesanan->kode_pesanan,
                    statusSaatIni: $pesanan->status_operasional,
                    statusTujuan: $statusTujuan,
                );
            }

            $pesanan->update([
                'status_operasional' => $statusTujuan,
            ]);

            return $pesanan->refresh();
        });
    }
}
