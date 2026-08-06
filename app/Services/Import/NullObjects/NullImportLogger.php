<?php

namespace App\Services\Import\NullObjects;

use App\Services\Import\Contracts\ImportLogger;
use App\Services\Import\DTO\ImportContext;

final class NullImportLogger implements ImportLogger
{
    public function stageStarted(string $stage, ImportContext $context): void {}

    public function stageCompleted(string $stage, ImportContext $context, mixed $payload): void {}
}
