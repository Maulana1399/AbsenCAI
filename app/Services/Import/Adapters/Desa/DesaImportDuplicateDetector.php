<?php

namespace App\Services\Import\Adapters\Desa;

use App\Models\desa;
use App\Services\Import\Contracts\ImportDuplicateDetector;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportSummary;

final class DesaImportDuplicateDetector implements ImportDuplicateDetector
{
    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function detect(array $rows, ImportContext $context): ImportSummary
    {
        $seen = [];
        $duplicateCount = 0;

        foreach ($rows as $row) {
            $key = $row->duplicateKey;

            if ($key === null || $key === '') {
                continue;
            }

            if (isset($seen[$key])) {
                $duplicateCount++;

                continue;
            }

            $seen[$key] = true;

            if ($this->existsInDatabase($key)) {
                $duplicateCount++;
            }
        }

        return new ImportSummary(
            totalRows: count($rows),
            validRows: count($rows) - $duplicateCount,
            duplicateRows: $duplicateCount,
        );
    }

    private function existsInDatabase(string $key): bool
    {
        return desa::whereRaw('LOWER(TRIM(desa_asal)) = ?', [$key])->exists();
    }
}
