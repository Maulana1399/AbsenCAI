<?php

namespace App\Services\Import\DTO;

final readonly class ImportWarning
{
    public function __construct(
        public int $rowNumber,
        public string $field,
        public string $message,
        public string $code = 'warning',
    ) {}
}
