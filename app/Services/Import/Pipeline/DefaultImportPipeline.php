<?php

namespace App\Services\Import\Pipeline;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportPipelineResult;
use App\Services\Import\Support\PipelineStageRunner;

final class DefaultImportPipeline implements ImportPipeline
{
    /**
     * Canonical, configurable stage order. Override via the constructor to
     * compose a custom pipeline without touching stage implementations.
     */
    public const DEFAULT_STAGES = [
        'parse',
        'normalize',
        'validate',
        'duplicate',
        'preview',
        'commit',
        'summary',
        'cleanup',
    ];

    /**
     * @param  array<int, string>  $stageOrder
     */
    public function __construct(
        private readonly PipelineStageRunner $runner,
        private readonly array $stageOrder = self::DEFAULT_STAGES,
    ) {}

    public function run(ImportDefinition $definition, ImportContext $context, mixed $source): ImportPipelineResult
    {
        $state = new ImportPipelineState($context, $definition, $source);

        return $this->runner->run($state, $this->stageOrder);
    }

    public function stageOrder(): array
    {
        return $this->stageOrder;
    }
}
