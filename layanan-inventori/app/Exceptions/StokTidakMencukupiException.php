<?php

namespace App\Exceptions;

use RuntimeException;

class StokTidakMencukupiException extends RuntimeException
{
    public function __construct(
        public readonly string $kodeMenu,
    ) {
        parent::__construct(
            "Stok menu {$kodeMenu} tidak mencukupi.",
        );
    }
}
