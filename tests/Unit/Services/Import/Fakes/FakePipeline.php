<?php

namespace Tests\Unit\Services\Import\Fakes;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Pipeline\ImportPipeline;
use App\Services\Import\Results\ImportPipelineResult;

class FakePipeline implements ImportPipeline
{
    public array $runs = [];

    public function run(ImportDefinition $definition, ImportContext $context, mixed $source): ImportPipelineResult
    {
        $this->runs[] = [
            'definition' => $definition->key(),
            'mode' => $context->mode,
            'source' => $source,
        ];

        return new ImportPipelineResult(status: 'completed');
    }

    public function stageOrder(): array
    {
        return [];
    }
}
