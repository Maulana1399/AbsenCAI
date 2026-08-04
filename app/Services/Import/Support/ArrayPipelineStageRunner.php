<?php

namespace App\Services\Import\Support;

use App\Services\Import\DTO\ImportContext;

final class ArrayPipelineStageRunner implements PipelineStageRunner
{
    public function run(string $stage, mixed $payload, ImportContext $context): mixed
    {
        return $payload;
    }
}
