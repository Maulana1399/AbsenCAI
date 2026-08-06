<?php

namespace App\Services\Import\Adapters\Desa;

use App\Models\desa;
use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;

final class DesaImportCommitter implements ImportCommitter
{
    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function commit(array $rows, ImportContext $context): ImportCommit
    {
        $createdIds = [];
        $skippedIds = [];
        $failedRows = [];

        foreach ($rows as $row) {
            $desa = trim($row->data['desa'] ?? '');

            if ($desa === '') {
                $failedRows[] = [
                    'row' => $row->rowNumber,
                    'message' => 'Nama desa wajib diisi.',
                ];

                continue;
            }

            if ($this->existsInDatabase($desa)) {
                $skippedIds[] = $row->rowNumber;

                continue;
            }

            $created = desa::create(['desa_asal' => $desa]);
            $createdIds[] = $created->id;
        }

        return new ImportCommit(
            context: $context,
            summary: new ImportSummary(
                totalRows: count($rows),
                createdRows: count($createdIds),
                skippedRows: count($skippedIds),
                invalidRows: count($failedRows),
            ),
            createdIds: $createdIds,
            skippedIds: $skippedIds,
            failedRows: $failedRows,
        );
    }

    private function existsInDatabase(string $name): bool
    {
        return desa::whereRaw('LOWER(TRIM(desa_asal)) = ?', [mb_strtolower($name)])->exists();
    }
}
