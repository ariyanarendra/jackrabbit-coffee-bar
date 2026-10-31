<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'pesanan_operasionals',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('event_id')->unique();
                $table->string('kode_pesanan')->unique();
                $table->string('kanal');
                $table->string('nama_pelanggan');
                $table->string('status_pesanan');
                $table->string('status_operasional')
                    ->default('menunggu_diproses');
                $table->unsignedBigInteger('total');
                $table->json('payload');
                $table->timestampTz('occurred_at');
                $table->timestampTz('received_at');
                $table->timestamps();
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan_operasionals');
    }
};
