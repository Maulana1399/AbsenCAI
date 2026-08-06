<?php

namespace App\Services\Import\Adapters\Desa;

use App\Services\Import\Contracts\ImportNormalizer;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\DTO\RawImportRow;

final class DesaImportNormalizer implements ImportNormalizer
{
    /**
     * @param  array<int, RawImportRow>  $rows
     * @return array<int, NormalizedImportRow>
     */
    public function normalize(array $rows, ImportContext $context): array
    {
        return array_map(function (RawImportRow $row) {
            $desa = $this->normalizeDesaName($row->raw['desa'] ?? '');

            return new NormalizedImportRow(
                rowNumber: $row->rowNumber,
                data: ['desa' => $desa],
                original: $row,
                duplicateKey: $desa !== '' ? mb_strtolower($desa) : null,
            );
        }, $rows);
    }

    private function normalizeDesaName(mixed $value): string
    {
        $name = trim((string) $value);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return $name;
    }
}
