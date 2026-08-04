<?php

namespace App\Services\Import\Results;

use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportSummary;

final readonly class ImportPreview
{
    public function __construct(
        public ImportContext $context,
        public array $rows,
        public ImportSummary $summary,
        public bool $canCommit,
        public ?string $blockedReason = null,
    ) {
    }
}
