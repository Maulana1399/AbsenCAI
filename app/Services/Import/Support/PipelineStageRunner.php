<?php

namespace App\Services\Import\Support;

use App\Services\Import\DTO\ImportContext;

interface PipelineStageRunner
{
    public function run(string $stage, mixed $payload, ImportContext $context): mixed;
}
