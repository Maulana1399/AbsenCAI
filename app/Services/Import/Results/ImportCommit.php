<?php

namespace App\Services\Import\Results;

use App\Services\Import\DTO\ImportContext;

final readonly class ImportCommit
{
    /**
     * @param  array<int, int>  $createdIds
     * @param  array<int, int>  $updatedIds
     * @param  array<int, int>  $skippedIds
     * @param  array<int, array<string, mixed>>  $failedRows
     * @param  array<string, mixed>  $metrics  module-specific counters (e.g. created_persons)
     */
    public function __construct(
        public ImportContext $context,
        public ImportSummary $summary,
        public array $createdIds = [],
        public array $updatedIds = [],
        public array $skippedIds = [],
        public array $failedRows = [],
        public bool $activityLogged = false,
        public array $metrics = [],
    ) {}
}
