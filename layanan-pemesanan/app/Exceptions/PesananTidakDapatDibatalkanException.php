<?php

namespace App\Exceptions;

use RuntimeException;

class PesananTidakDapatDibatalkanException extends RuntimeException
{
    public function __construct(
        public readonly string $kodePesanan,
        public readonly string $statusPesanan,
    ) {
        parent::__construct(
            "Pesanan {$kodePesanan} tidak dapat dibatalkan karena statusnya {$statusPesanan}.",
        );
    }
}
