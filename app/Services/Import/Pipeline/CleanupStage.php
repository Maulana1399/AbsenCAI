<?php

namespace App\Services\Import\Pipeline;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\Contracts\ImportPipelineStage;
use App\Services\Import\DTO\ImportContext;

/**
 * Terminal stage hook for releasing transient resources (temp files, buffers).
 * No-op by default; definitions or consumers may extend behavior later.
 */
final class CleanupStage implements ImportPipelineStage
{
    public function name(): string
    {
        return 'cleanup';
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
        return $payload;
    }
}
