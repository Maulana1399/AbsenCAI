<?php

namespace App\Services\Import\Adapters\Desa;

use App\Services\Import\Contracts\ImportValidator;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportSummary;
use App\Services\Import\DTO\NormalizedImportRow;

final class DesaImportValidator implements ImportValidator
{
    public function validate(array $rows, ImportContext $context): ImportSummary
    {
        return new ImportSummary(totalRows: count($rows), validRows: count($rows));
    }
}
