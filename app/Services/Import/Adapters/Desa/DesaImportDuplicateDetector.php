<?php

namespace App\Services\Import\Adapters\Desa;

use App\Services\Import\Contracts\ImportDuplicateDetector;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportSummary;

final class DesaImportDuplicateDetector implements ImportDuplicateDetector
{
    public function detect(array $rows, ImportContext $context): ImportSummary
    {
        return new ImportSummary(totalRows: count($rows), validRows: count($rows));
    }
}
