<?php

namespace App\Services\Import\Metrics;

use App\Services\Import\DTO\ImportContext;

final class ImportMetrics
{
    public function __construct(
        public ?float $duration = null,
        public ?int $memory = null,
        public int $totalRows = 0,
        public int $validRows = 0,
        public int $invalidRows = 0,
        public int $duplicateRows = 0,
        public int $createdRows = 0,
        public int $updatedRows = 0,
        public int $skippedRows = 0,
        public ?ImportContext $context = null,
    ) {}
}
