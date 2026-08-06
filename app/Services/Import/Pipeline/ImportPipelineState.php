<?php

namespace App\Services\Import\Pipeline;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportPreview;
use App\Services\Import\Results\ImportSummary;

/**
 * Mutable, per-run accumulator flowing through the pipeline.
 *
 * Inputs are immutable (ImportContext + ImportDefinition). Everything produced
 * during execution (rows, summaries, preview, commit, statistics) lives here so
 * the ImportContext itself never needs to be mutated.
 */
final class ImportPipelineState
{
    public mixed $payload;

    public ?array $rows = null;

    public ?ImportSummary $validationSummary = null;

    public ?ImportSummary $duplicateSummary = null;

    public ?ImportSummary $summary = null;

    public ?ImportPreview $preview = null;

    public ?ImportCommit $commit = null;

    public array $executedStages = [];

    public array $skippedStages = [];

    public array $statistics = [];

    public function __construct(
        public readonly ImportContext $context,
        public readonly ImportDefinition $definition,
        mixed $payload,
    ) {
        $this->payload = $payload;
    }
}
