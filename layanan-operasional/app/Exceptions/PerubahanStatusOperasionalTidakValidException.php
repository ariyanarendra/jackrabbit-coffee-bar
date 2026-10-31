<?php

namespace App\Exceptions;

use RuntimeException;

class PerubahanStatusOperasionalTidakValidException extends RuntimeException
{
    public function __construct(
        string $kodePesanan,
        string $statusSaatIni,
        string $statusTujuan,
    ) {
        parent::__construct(
            "Pesanan {$kodePesanan} tidak dapat diubah dari status ".
            "{$statusSaatIni} menjadi {$statusTujuan}.",
        );
    }
}
