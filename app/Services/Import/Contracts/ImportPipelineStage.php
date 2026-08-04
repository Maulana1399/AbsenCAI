<?php

namespace App\Services\Import\Contracts;

use App\Services\Import\DTO\ImportContext;

interface ImportPipelineStage
{
    public function handle(mixed $payload, ImportContext $context): mixed;
}
