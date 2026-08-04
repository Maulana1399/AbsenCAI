<?php

namespace App\Services\Import\Pipeline;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportPipelineResult;

interface ImportPipeline
{
    public function run(ImportDefinition $definition, ImportContext $context, mixed $source): ImportPipelineResult;
}
