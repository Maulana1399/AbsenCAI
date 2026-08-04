<?php

namespace App\Services\Import\Pipeline;

use App\Services\Import\DTO\ImportContext;

final class CommitStage
{
    public function handle(mixed $payload, ImportContext $context): mixed
    {
        return $payload;
    }
}
