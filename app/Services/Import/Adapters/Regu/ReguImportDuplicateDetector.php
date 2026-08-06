<?php

namespace App\Services\Import\Adapters\Regu;

use App\Models\regu;
use App\Services\Import\Contracts\ImportDuplicateDetector;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportSummary;

final class ReguImportDuplicateDetector implements ImportDuplicateDetector
{
    /**
     * Business rule: a regu name is unique (`unique:regus,regu`).
     *
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
        return regu::whereRaw('LOWER(TRIM(regu)) = ?', [$key])->exists();
    }
}
