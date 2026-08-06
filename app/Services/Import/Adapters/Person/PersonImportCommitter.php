<?php

namespace App\Services\Import\Adapters\Person;

use App\Models\Person;
use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;
use App\Services\Person\PersonDuplicateDetectionService;

final class PersonImportCommitter implements ImportCommitter
{
    public function __construct(
        private readonly PersonDuplicateDetectionService $duplicateService,
    ) {}

    /**
     * Creates Person records following Design C. Duplicate detection reuses the
     * canonical PersonDuplicateDetectionService; creation follows the golden
     * path (`Person::create`) since no standalone PersonService exists.
     *
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function commit(array $rows, ImportContext $context): ImportCommit
    {
        $createdIds = [];
        $skippedIds = [];
        $failedRows = [];

        foreach ($rows as $row) {
            $nama = trim($row->data['nama'] ?? '');
            $gender = $row->data['jenis_kelamin'] ?? null;

            if ($nama === '' || ! in_array($gender, ['L', 'P'], true)) {
                $failedRows[] = [
                    'row' => $row->rowNumber,
                    'message' => $nama === '' ? 'Nama wajib diisi.' : 'Jenis kelamin harus Laki-laki (L) atau Perempuan (P).',
                ];

                continue;
            }

            $result = $this->duplicateService->detect([
                'nama' => $nama,
                'tanggal_lahir' => $row->data['tanggal_lahir'] ?? null,
                'jenis_kelamin' => $gender,
                'desa_id' => $row->data['desa_id'] ?? null,
            ]);

            if (! empty($result['strong'])) {
                $skippedIds[] = $row->rowNumber;

                continue;
            }

            $person = Person::create([
                'nama' => $nama,
                'jenis_kelamin' => $gender,
                'tanggal_lahir' => $row->data['tanggal_lahir'] ?: null,
                'desa_id' => $row->data['desa_id'] ?? null,
                'kelompok_id' => $row->data['kelompok_id'] ?? null,
            ]);
            $createdIds[] = $person->id;
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
}
