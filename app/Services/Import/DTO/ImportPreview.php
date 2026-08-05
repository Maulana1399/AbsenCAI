<?php

namespace App\Services\Import\DTO;

final readonly class ImportPreview
{
    public function __construct(
        public ImportContext $context,
        public array $rows,
        public ImportSummary $summary,
        public bool $canCommit,
        public ?string $blockedReason = null,
    ) {}
}
