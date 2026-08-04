<?php

namespace App\Services\Import\Contracts;

use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\RawImportRow;

interface ImportParser
{
    /**
     * @return array<int, RawImportRow>
     */
    public function parse(mixed $source, ImportContext $context): array;
}
