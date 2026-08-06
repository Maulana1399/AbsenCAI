<?php

namespace App\Services\Import\Exceptions;

use App\Services\Import\Contracts\ImportDefinition;

final class ImportVersionNotSupportedException extends ImportException
{
    public function __construct(string $version, ImportDefinition $definition)
    {
        parent::__construct(
            "Versi '{$version}' tidak didukung oleh import '{$definition->key()}'. ".
            "Rentang didukung: {$definition->minimumVersion()} - {$definition->currentVersion()}."
        );
    }
}
