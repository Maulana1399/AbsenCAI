<?php

namespace App\Services\Import\DTO;

use App\Services\Import\Results\ImportError;
use App\Services\Import\Results\ImportWarning;

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
    ) {
    }
}
