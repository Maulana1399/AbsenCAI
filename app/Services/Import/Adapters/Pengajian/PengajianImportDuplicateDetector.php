<?php

namespace App\Services\Import\Adapters\Pengajian;

use App\Services\Import\Contracts\ImportDuplicateDetector;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportSummary;

/**
 * Pengajian duplicate handling happens inside the committer (exact golden
 * behavior: person match during commit, participation duplicate skip). The
 * preview never showed duplicates, so this detector is intentionally a no-op
 * to avoid changing behavior or adding queries at preview time.
 */
final class PengajianImportDuplicateDetector implements ImportDuplicateDetector
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
