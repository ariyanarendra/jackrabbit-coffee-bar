<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasKodePesanan = Schema::hasColumn(
            'pesanans',
            'kode_pesanan',
        );

        $hasKanal = Schema::hasColumn(
            'pesanans',
            'kanal',
        );

        $hasNamaPelanggan = Schema::hasColumn(
            'pesanans',
            'nama_pelanggan',
        );

        $hasStatus = Schema::hasColumn(
            'pesanans',
            'status',
        );

        $hasTotal = Schema::hasColumn(
            'pesanans',
            'total',
        );

        Schema::table('pesanans', function (Blueprint $table) use (
            $hasKodePesanan,
            $hasKanal,
            $hasNamaPelanggan,
            $hasStatus,
            $hasTotal,
        ): void {
            if (! $hasKodePesanan) {
                $table->string('kode_pesanan')
                    ->nullable()
                    ->unique()
                    ->after('id');
            }

            if (! $hasKanal) {
                $table->string('kanal')
                    ->nullable()
                    ->after('kode_pesanan');
            }

            if (! $hasNamaPelanggan) {
                $table->string('nama_pelanggan')
                    ->nullable()
                    ->after('kanal');
            }

            if (! $hasStatus) {
                $table->string('status')
                    ->default('menunggu')
                    ->after('nama_pelanggan');
            }

            if (! $hasTotal) {
                $table->unsignedBigInteger('total')
                    ->default(0)
                    ->after('status');
            }
        });
    }

    public function down(): void
    {
        /*
         * Migrasi perbaikan ini sengaja tidak menghapus kolom saat rollback.
         * Pada instalasi baru, kolom-kolom tersebut sudah dibuat oleh migrasi
         * awal sehingga menghapusnya akan merusak struktur tabel.
         */
    }
};
