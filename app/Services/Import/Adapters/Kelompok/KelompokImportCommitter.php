<?php

namespace App\Services\Import\Adapters\Kelompok;

use App\Models\kelompok;
use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;

final class KelompokImportCommitter implements ImportCommitter
{
    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function commit(array $rows, ImportContext $context): ImportCommit
    {
        $createdIds = [];
        $skippedIds = [];
        $failedRows = [];

        $desaId = (int) ($context->options['parameters']['desa_id'] ?? 0);

        foreach ($rows as $row) {
            $kelompok = trim($row->data['kelompok'] ?? '');

            if ($kelompok === '' || $desaId <= 0) {
                $failedRows[] = [
                    'row' => $row->rowNumber,
                    'message' => $kelompok === '' ? 'Nama kelompok wajib diisi.' : 'Desa tidak valid.',
                ];

                continue;
            }

            if ($this->existsInDatabase($kelompok, $desaId)) {
                $skippedIds[] = $row->rowNumber;

                continue;
            }

            $created = kelompok::create([
                'kelompok_asal' => $kelompok,
                'desa_id' => $desaId,
            ]);
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

    private function existsInDatabase(string $kelompok, int $desaId): bool
    {
        return kelompok::query()
            ->where('desa_id', $desaId)
            ->whereRaw('LOWER(TRIM(kelompok_asal)) = ?', [mb_strtolower($kelompok)])
            ->exists();
    }
}
