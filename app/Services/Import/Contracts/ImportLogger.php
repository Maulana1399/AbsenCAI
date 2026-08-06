<?php

namespace App\Services\Import\Contracts;

use App\Services\Import\DTO\ImportContext;

/**
 * Optional pipeline observation hook. Intentionally minimal — the framework
 * never writes debug logs itself; consumers that want observability implement
 * this and wire it into the runner.
 */
interface ImportLogger
{
    public function stageStarted(string $stage, ImportContext $context): void;

    public function stageCompleted(string $stage, ImportContext $context, mixed $payload): void;
}
