<?php

namespace App\Exceptions;

use RuntimeException;

class KonflikIdempotensiException extends RuntimeException
{
    public function __construct(
        public readonly string $kodePesanan,
    ) {
        parent::__construct(
            "Kode pesanan {$kodePesanan} sudah digunakan dengan payload yang berbeda.",
        );
    }
}
