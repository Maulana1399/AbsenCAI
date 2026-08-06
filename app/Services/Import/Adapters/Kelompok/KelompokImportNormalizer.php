<?php

namespace App\Services\Import\Adapters\Kelompok;

use App\Services\Import\Contracts\ImportNormalizer;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\DTO\RawImportRow;

final class KelompokImportNormalizer implements ImportNormalizer
{
    /**
     * @param  array<int, RawImportRow>  $rows
     * @return array<int, NormalizedImportRow>
     */
    public function normalize(array $rows, ImportContext $context): array
    {
        $desaId = (int) ($context->options['parameters']['desa_id'] ?? 0);

        return array_map(function (RawImportRow $row) use ($desaId) {
            $kelompok = $this->normalizeKelompokName($row->raw['kelompok'] ?? '');

            return new NormalizedImportRow(
                rowNumber: $row->rowNumber,
                data: ['kelompok' => $kelompok, 'desa_id' => $desaId],
                original: $row,
                duplicateKey: $kelompok !== '' && $desaId > 0
                    ? $desaId.'|'.mb_strtolower($kelompok)
                    : null,
            );
        }, $rows);
    }

    private function normalizeKelompokName(mixed $value): string
    {
        $name = trim((string) $value);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return $name;
    }
}
