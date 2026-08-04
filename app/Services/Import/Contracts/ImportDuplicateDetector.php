<?php

namespace App\Services\Import\Contracts;

use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportSummary as ImportSummaryResult;
use App\Services\Import\DTO\NormalizedImportRow;

interface ImportDuplicateDetector
{
    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function detect(array $rows, ImportContext $context): ImportSummaryResult;
}
