<?php

namespace App\Services\Import\Adapters\Pengajian;

use App\Services\Import\Contracts\ImportNormalizer;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\DTO\RawImportRow;

/**
 * Pengajian normalizer — preserves raw values unchanged. The golden Pengajian
 * flow normalizes inline during commit (strtoupper gender, trim nama, date
 * check), so the pipeline keeps the raw row untouched to guarantee identical
 * output.
 */
final class PengajianImportNormalizer implements ImportNormalizer
{
    /**
     * @param  array<int, RawImportRow>  $rows
     * @return array<int, NormalizedImportRow>
     */
    public function normalize(array $rows, ImportContext $context): array
    {
        return array_map(
            fn (RawImportRow $row) => new NormalizedImportRow($row->rowNumber, $row->raw, $row),
            $rows,
        );
    }
}
