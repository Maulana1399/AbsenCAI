<?php

namespace App\Services\Import\Exceptions;

use App\Services\Import\DTO\ImportContext;

final class ImportStageNotFoundException extends ImportException
{
    public function __construct(string $stage, ?ImportContext $context = null)
    {
        $suffix = $context !== null ? " (definition: '{$context->definitionKey}')" : '';

        parent::__construct("Pipeline stage '{$stage}' tidak ditemukan{$suffix}.");
    }
}
