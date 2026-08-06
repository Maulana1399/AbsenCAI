<?php

namespace App\Services\Import\Adapters\Peserta;

use App\Services\Import\Contracts\ImportDuplicateDetector;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportSummary;

/**
 * Peserta duplicate handling happens inside RegistrationService during commit
 * (person reuse / same-event membership guard). The legacy import never showed
 * preview duplicates, so this detector is intentionally a no-op.
 */
final class PesertaImportDuplicateDetector implements ImportDuplicateDetector
{
    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function detect(array $rows, ImportContext $context): ImportSummary
    {
        return new ImportSummary(
            totalRows: count($rows),
            validRows: count($rows),
        );
    }
}
