<?php

namespace App\Services\Import\Exceptions;

final class ImportDefinitionNotFoundException extends ImportException
{
    public function __construct(string $definitionKey)
    {
        parent::__construct("Import definition '{$definitionKey}' tidak ditemukan di registry.");
    }
}
