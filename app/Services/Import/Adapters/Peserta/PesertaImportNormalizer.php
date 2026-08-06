<?php

namespace App\Services\Import\Adapters\Peserta;

use App\Services\Import\Contracts\ImportNormalizer;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\DTO\RawImportRow;

/**
 * Peserta normalizer — preserves raw values unchanged. The legacy flow resolved
 * desa/kelompok and created participants inside the Maatwebsite model(); that
 * logic now lives in the committer, so the pipeline keeps the raw row.
 */
final class PesertaImportNormalizer implements ImportNormalizer
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
