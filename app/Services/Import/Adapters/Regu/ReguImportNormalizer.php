<?php

namespace App\Services\Import\Adapters\Regu;

use App\Services\Import\Contracts\ImportNormalizer;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\DTO\RawImportRow;

final class ReguImportNormalizer implements ImportNormalizer
{
    /**
     * @param  array<int, RawImportRow>  $rows
     * @return array<int, NormalizedImportRow>
     */
    public function normalize(array $rows, ImportContext $context): array
    {
        return array_map(function (RawImportRow $row) {
            $regu = $this->normalizeReguName($row->raw['regu'] ?? '');
            $jenisKelamin = $this->normalizeJenisKelamin($row->raw['jenis_kelamin'] ?? null);

            return new NormalizedImportRow(
                rowNumber: $row->rowNumber,
                data: ['regu' => $regu, 'jenis_kelamin' => $jenisKelamin],
                original: $row,
                duplicateKey: $regu !== '' ? mb_strtolower($regu) : null,
            );
        }, $rows);
    }

    private function normalizeReguName(mixed $value): string
    {
        $name = trim((string) $value);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return $name;
    }

    /**
     * Business rule preserved from the legacy `ReguImport::normalizeJenisKelamin`:
     * dash variants and case-insensitive forms normalize to the enum values.
     */
    private function normalizeJenisKelamin(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        $value = preg_replace('/[–—‑]+/u', '-', $value) ?? $value;
        $value = preg_replace('/\s*-\s*/u', ' - ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        if (preg_match('/^laki\s*-\s*laki$/iu', $value) || preg_match('/^laki\s+laki$/iu', $value)) {
            return 'Laki - Laki';
        }

        if (preg_match('/^perempuan$/iu', $value)) {
            return 'Perempuan';
        }

        return $value;
    }
}
