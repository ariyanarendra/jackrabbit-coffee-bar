<?php

namespace App\Exceptions;

use RuntimeException;

class StatusPesananTidakValidException extends RuntimeException
{
    public function __construct(
        public readonly string $kodePesanan,
        public readonly string $statusPesanan,
    ) {
        parent::__construct(
            "Pesanan {$kodePesanan} tidak dapat dikonfirmasi karena statusnya {$statusPesanan}.",
        );
    }
}
