<?php

namespace App\Services\Import\Pipeline;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\Contracts\ImportPipelineStage;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportPreview;
use App\Services\Import\Results\ImportSummary;

/**
 * Builds the ImportPreview consumed by the wizard (data + per-row status +
 * canCommit). Only runs when the definition supports previews.
 */
final class PreviewStage implements ImportPipelineStage
{
    public function name(): string
    {
        return 'preview';
    }

    public function supports(ImportContext $context, ImportDefinition $definition): bool
    {
        return $definition->supportsPreview($context);
    }

    public function handle(
        mixed $payload,
        ImportContext $context,
        ImportDefinition $definition,
        ImportPipelineState $state,
    ): mixed {
        $rows = $state->rows ?? [];
        $summary = $state->validationSummary ?? new ImportSummary;

        $canCommit = $summary->invalidRows === 0
            && empty($summary->errors)
            && count($rows) > 0;

        $state->preview = new ImportPreview(
            context: $context,
            rows: $rows,
            summary: $summary,
            canCommit: $canCommit,
            blockedReason: $canCommit ? null : 'Terdapat baris yang gagal validasi.',
        );

        return $rows;
    }
}
