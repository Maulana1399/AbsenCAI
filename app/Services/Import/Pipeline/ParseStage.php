<?php

namespace App\Services\Import\Pipeline;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\Contracts\ImportPipelineStage;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Exceptions\ImportParseException;

/**
 * Turns the raw source (uploaded file / array) into RawImportRow[].
 */
final class ParseStage implements ImportPipelineStage
{
    public function name(): string
    {
        return 'parse';
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
            $rows = $definition->parser()->parse($payload, $context);
        } catch (\Throwable $e) {
            throw new ImportParseException(
                "Gagal membaca file untuk import '{$definition->key()}': {$e->getMessage()}",
                previous: $e,
            );
        }

        $state->rows = $rows;
        $state->statistics['parsed_rows'] = count($rows);

        return $rows;
    }
}
