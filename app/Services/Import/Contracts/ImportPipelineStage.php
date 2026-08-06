<?php

namespace App\Services\Import\Contracts;

use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Pipeline\ImportPipelineState;

/**
 * Uniform contract for every pipeline stage.
 *
 * A stage:
 *  - exposes its name (used to build a configurable, non-hardcoded stage order),
 *  - declares whether it applies to the given import (supports),
 *  - runs the actual logic (handle), mutating the mutable ImportPipelineState
 *    and returning the next payload for the following stage.
 */
interface ImportPipelineStage
{
    public function name(): string;

    public function supports(ImportContext $context, ImportDefinition $definition): bool;

    public function handle(
        mixed $payload,
        ImportContext $context,
        ImportDefinition $definition,
        ImportPipelineState $state,
    ): mixed;
}
