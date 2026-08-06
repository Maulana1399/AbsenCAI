<?php

namespace App\Services\Import\Adapters\Regu;

use App\Models\regu;
use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;

final class ReguImportCommitter implements ImportCommitter
{
    private const GENDERS = ['Laki - Laki', 'Perempuan'];

    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function commit(array $rows, ImportContext $context): ImportCommit
    {
        $createdIds = [];
        $skippedIds = [];
        $failedRows = [];

        foreach ($rows as $row) {
            $regu = trim($row->data['regu'] ?? '');
            $gender = $row->data['jenis_kelamin'] ?? null;

            if ($regu === '' || ! in_array($gender, self::GENDERS, true)) {
                $failedRows[] = [
                    'row' => $row->rowNumber,
                    'message' => $regu === '' ? 'Nama regu wajib diisi.' : 'Jenis kelamin harus Laki - laki atau Perempuan.',
                ];

                continue;
            }

            if ($this->existsInDatabase($regu)) {
                $skippedIds[] = $row->rowNumber;

                continue;
            }

            $created = regu::create([
                'regu' => $regu,
                'jenis_kelamin' => $gender,
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

    private function existsInDatabase(string $regu): bool
    {
        return regu::whereRaw('LOWER(TRIM(regu)) = ?', [mb_strtolower($regu)])->exists();
    }
}
