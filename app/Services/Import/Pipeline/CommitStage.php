<?php

namespace App\Services\Import\Pipeline;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\Contracts\ImportPipelineStage;
use App\Services\Import\DTO\ImportContext;

/**
 * Executes the definition's committer.
 *
 * Deliberately does NOT wrap commit exceptions: business failures such as
 * Maatwebsite's ValidationException must propagate unchanged so the legacy
 * controllers keep their current error handling behavior.
 */
final class CommitStage implements ImportPipelineStage
{
    public function name(): string
    {
        return 'commit';
    }

    public function supports(ImportContext $context, ImportDefinition $definition): bool
    {
        return $context->mode === 'execute' && $definition->supportsCommit($context);
    }

    public function handle(
        mixed $payload,
        ImportContext $context,
        ImportDefinition $definition,
        ImportPipelineState $state,
    ): mixed {
        $commit = $definition->committer()->commit($context);

        $state->commit = $commit;

        $state->statistics = array_merge($state->statistics, [
            'created_rows' => $commit->summary->createdRows,
            'updated_rows' => $commit->summary->updatedRows,
            'skipped_rows' => $commit->summary->skippedRows,
        ]);

        return $commit;
    }
}
