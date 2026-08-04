<?php

namespace App\Services\Import\DTO;

final readonly class ImportError
{
    public function __construct(
        public int $rowNumber,
        public string $field,
        public string $message,
        public string $code = 'validation_error',
        public string $severity = 'error',
    ) {
    }
}
