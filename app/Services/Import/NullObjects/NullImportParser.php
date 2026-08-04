<?php

namespace App\Services\Import\NullObjects;

use App\Services\Import\Contracts\ImportParser;
use App\Services\Import\DTO\ImportContext;

final class NullImportParser implements ImportParser
{
    public function parse(mixed $source, ImportContext $context): array
    {
        return [];
    }
}
