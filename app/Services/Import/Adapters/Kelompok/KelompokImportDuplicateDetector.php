<?php

namespace App\Services\Import\Adapters\Kelompok;

use App\Models\kelompok;
use App\Services\Import\Contracts\ImportDuplicateDetector;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportSummary;

final class KelompokImportDuplicateDetector implements ImportDuplicateDetector
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

            if ($this->existsInDatabase($row)) {
                $duplicateCount++;
            }
        }

        return new ImportSummary(
            totalRows: count($rows),
            validRows: count($rows) - $duplicateCount,
            duplicateRows: $duplicateCount,
        );
    }

    private function existsInDatabase(NormalizedImportRow $row): bool
    {
        return kelompok::query()
            ->where('desa_id', (int) ($row->data['desa_id'] ?? 0))
            ->whereRaw('LOWER(TRIM(kelompok_asal)) = ?', [mb_strtolower(trim($row->data['kelompok'] ?? ''))])
            ->exists();
    }
}
