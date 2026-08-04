<?php

namespace App\Services\Import\Contracts;

use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportSummary;
use App\Services\Import\DTO\NormalizedImportRow;

interface ImportValidator
{
    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function validate(array $rows, ImportContext $context): ImportSummary;
}
