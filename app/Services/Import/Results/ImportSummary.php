<?php

namespace App\Services\Import\Results;

final readonly class ImportSummary
{
    /**
     * @param  array<int, ImportError>  $errors
     * @param  array<int, ImportWarning>  $warnings
     */
    public function __construct(
        public int $totalRows = 0,
        public int $validRows = 0,
        public int $invalidRows = 0,
        public int $duplicateRows = 0,
        public int $createdRows = 0,
        public int $updatedRows = 0,
        public int $skippedRows = 0,
        public array $errors = [],
        public array $warnings = [],
    ) {}
}
