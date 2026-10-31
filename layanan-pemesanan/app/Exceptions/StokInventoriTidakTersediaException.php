<?php

namespace App\Exceptions;

use RuntimeException;

class StokInventoriTidakTersediaException extends RuntimeException
{
    public function __construct(
        string $detail = 'Stok Inventori tidak tersedia untuk memenuhi pesanan.',
    ) {
        parent::__construct($detail);
    }
}
