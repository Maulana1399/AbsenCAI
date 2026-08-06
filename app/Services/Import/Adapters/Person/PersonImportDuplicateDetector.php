<?php

namespace App\Services\Import\Adapters\Person;

use App\Services\Import\Contracts\ImportDuplicateDetector;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportWarning;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportSummary;
use App\Services\Person\PersonDuplicateDetectionService;

final class PersonImportDuplicateDetector implements ImportDuplicateDetector
{
    public function __construct(
        private readonly PersonDuplicateDetectionService $duplicateService,
    ) {}

    /**
     * Duplicate follows the canonical PersonDuplicateDetectionService
     * (normalized name + tanggal lahir). Rows with possible identity collisions
     * are surfaced as warnings.
     *
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function detect(array $rows, ImportContext $context): ImportSummary
    {
        $seen = [];
        $duplicateCount = 0;
        $warnings = [];

        foreach ($rows as $row) {
            $key = $row->duplicateKey;

            if ($key !== null && $key !== '') {
                if (isset($seen[$key])) {
                    $duplicateCount++;

                    continue;
                }

                $seen[$key] = true;
            }

            $result = $this->duplicateService->detect($this->identity($row));

            if (! empty($result['strong'])) {
                $duplicateCount++;

                continue;
            }

            if (! empty($result['possible'])) {
                $warnings[] = new ImportWarning(
                    rowNumber: $row->rowNumber,
                    field: 'nama',
                    message: 'Terdapat kandidat Person serupa. Verifikasi identitas manual.',
                );
            }
        }

        return new ImportSummary(
            totalRows: count($rows),
            validRows: count($rows) - $duplicateCount,
            duplicateRows: $duplicateCount,
            warnings: $warnings,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function identity(NormalizedImportRow $row): array
    {
        return [
            'nama' => $row->data['nama'] ?? '',
            'tanggal_lahir' => $row->data['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $row->data['jenis_kelamin'] ?? null,
            'desa_id' => $row->data['desa_id'] ?? null,
        ];
    }
}
