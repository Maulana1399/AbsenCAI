<?php

namespace App\Services\Import\Adapters\Desa;

use App\Services\Import\Contracts\ImportNormalizer;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\DTO\RawImportRow;

final class DesaImportNormalizer implements ImportNormalizer
{
    public function normalize(array $rows, ImportContext $context): array
    {
        return array_map(
            fn (RawImportRow $row) => new NormalizedImportRow($row->rowNumber, $row->raw, $row),
            $rows,
        );
    }
}
