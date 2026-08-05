<?php

namespace App\Services\Import\Adapters\Desa;

use App\Services\Import\Contracts\ImportParser;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\RawImportRow;

final class DesaImportParser implements ImportParser
{
    public function parse(mixed $source, ImportContext $context): array
    {
        return [new RawImportRow(1, 0, ['source' => $source], ['source' => $source])];
    }
}
