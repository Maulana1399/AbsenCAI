<?php

namespace App\Services\Import\Pipeline;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\Contracts\ImportPipelineStage;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Exceptions\ImportValidationException;

/**
 * Runs the definition's row validator and records the validation summary.
 */
final class ValidateStage implements ImportPipelineStage
{
    public function name(): string
    {
        return 'validate';
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
        try {
            $summary = $definition->validator()->validate($state->rows ?? [], $context);
        } catch (\Throwable $e) {
            throw new ImportValidationException(
                "Validasi import '{$definition->key()}' gagal: {$e->getMessage()}",
                previous: $e,
            );
        }

        $state->validationSummary = $summary;
        $state->statistics['valid_rows'] = $summary->validRows;
        $state->statistics['invalid_rows'] = $summary->invalidRows;

        return $state->rows;
    }
}
