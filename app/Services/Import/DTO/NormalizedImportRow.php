<?php

namespace App\Services\Import\DTO;

final readonly class NormalizedImportRow
{
    public function __construct(
        public int $rowNumber,
        public array $data,
        public RawImportRow $original,
        public array $warnings = [],
        public ?string $duplicateKey = null,
        public bool $isDuplicateCandidate = false,
    ) {
    }
}
