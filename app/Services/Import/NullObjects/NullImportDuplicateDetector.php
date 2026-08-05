<?php

namespace App\Services\Import\NullObjects;

use App\Services\Import\Contracts\ImportDuplicateDetector;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportSummary;

final class NullImportDuplicateDetector implements ImportDuplicateDetector
{
    public function detect(array $rows, ImportContext $context): ImportSummary
    {
        return new ImportSummary(totalRows: count($rows), validRows: count($rows));
    }
}
