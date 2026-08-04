<?php

namespace App\Services\Import\Pipeline;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportPipelineResult;
use App\Services\Import\Results\ImportPreview;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;
use App\Services\Import\Support\PipelineStageRunner;

final class ImportCoordinator
{
    public function __construct(
        private readonly \App\Services\Import\Registry\ImportRegistry $registry,
        private readonly ImportPipeline $pipeline,
    ) {
    }

    public function execute(string $definitionKey, ImportContext $context, mixed $source): ImportPipelineResult
    {
        $definition = $this->registry->resolve($definitionKey);

        if ($definition === null) {
            return new ImportPipelineResult('definition_not_found');
        }

        return $this->pipeline->run($definition, $context, $source);
    }
}
