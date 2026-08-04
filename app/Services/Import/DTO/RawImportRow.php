<?php

namespace App\Services\Import\DTO;

final readonly class RawImportRow
{
    public function __construct(
        public int $rowNumber,
        public int $sourceIndex,
        public array $raw,
        public array $originalValues,
        public ?string $sourceFileName = null,
        public array $errors = [],
    ) {
    }
}
