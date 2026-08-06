<?php

namespace App\Services\Import\Results;

final readonly class ImportPipelineResult
{
    public function __construct(
        public string $status,
        public ?ImportPreview $preview = null,
        public ?ImportCommit $commit = null,
        public ?ImportSummary $summary = null,
        public array $stages = [],
        public array $rows = [],
        public array $statistics = [],
    ) {}
}
