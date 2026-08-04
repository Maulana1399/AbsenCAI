<?php

namespace App\Services\Import\DTO;

use App\Services\Import\Results\ImportSummary;

final readonly class ImportCommit
{
    /**
     * @param  array<int, int>  $createdIds
     * @param  array<int, int>  $matchedIds
     * @param  array<int, int>  $skippedIds
     * @param  array<int, array<string, mixed>>  $failedRows
     */
    public function __construct(
        public ImportContext $context,
        public ImportSummary $summary,
        public array $createdIds = [],
        public array $matchedIds = [],
        public array $skippedIds = [],
        public array $failedRows = [],
        public bool $activityLogged = false,
    ) {
    }
}
