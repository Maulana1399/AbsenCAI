<?php

namespace App\Services\Import\Support;

use App\Services\Import\Pipeline\ImportPipelineState;
use App\Services\Import\Results\ImportPipelineResult;

/**
 * Executes pipeline stages in the configured order against a mutable
 * ImportPipelineState. Implementations must actually dispatch every stage
 * (never swallow or short-circuit) and resolve stages by name so the stage
 * order stays fully configurable.
 */
interface PipelineStageRunner
{
    /**
     * @param  array<int, string>  $stageOrder
     */
    public function run(ImportPipelineState $state, array $stageOrder): ImportPipelineResult;
}
