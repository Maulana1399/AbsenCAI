<?php

namespace App\Services\Import\Pipeline;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\Contracts\ImportPipelineStage;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\Results\ImportSummary;

/**
 * Aggregates the final ImportSummary from validation, duplicate detection and
 * commit results.
 */
final class SummaryStage implements ImportPipelineStage
{
    public function name(): string
    {
        return 'summary';
    }

    public function supports(ImportContext $context, ImportDefinition $definition): bool
    {
        return true;
    }

    public function handle(
        mixed $payload,
        ImportContext $context,
        ImportDefinition $definition,
        ImportPipelineState $state,
    ): mixed {
        $validation = $state->validationSummary ?? new ImportSummary;
        $duplicate = $state->duplicateSummary ?? new ImportSummary;
        $commit = $state->commit;

        $rows = $state->rows ?? [];
        $errors = $validation->errors;

        foreach ($commit?->failedRows ?? [] as $row) {
            $errors[] = new ImportError(
                rowNumber: (int) ($row['row'] ?? 0),
                field: 'row',
                message: (string) ($row['message'] ?? 'Baris gagal di-import.'),
            );
        }

        $total = max(count($rows), $validation->totalRows);
        $invalid = $validation->invalidRows > 0 ? $validation->invalidRows : count($validation->errors);

        $state->summary = new ImportSummary(
            totalRows: $total,
            validRows: max(0, $total - $invalid),
            invalidRows: $invalid,
            duplicateRows: $duplicate->duplicateRows,
            createdRows: $commit?->summary->createdRows ?? 0,
            updatedRows: $commit?->summary->updatedRows ?? 0,
            skippedRows: $commit?->summary->skippedRows ?? 0,
            errors: $errors,
            warnings: array_merge($validation->warnings, $duplicate->warnings),
        );

        return $payload;
    }
}
