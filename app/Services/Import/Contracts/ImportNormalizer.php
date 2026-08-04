<?php

namespace App\Services\Import\Contracts;

use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;

interface ImportNormalizer
{
    /**
     * @param  array<int, \App\Services\Import\DTO\RawImportRow>  $rows
     * @return array<int, NormalizedImportRow>
     */
    public function normalize(array $rows, ImportContext $context): array;
}
