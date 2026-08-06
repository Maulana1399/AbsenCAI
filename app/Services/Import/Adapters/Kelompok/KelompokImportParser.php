<?php

namespace App\Services\Import\Adapters\Kelompok;

use App\Services\Import\Contracts\ImportParser;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\RawImportRow;
use App\Services\Import\Support\FileParser;

final class KelompokImportParser implements ImportParser
{
    public function __construct(
        private readonly FileParser $fileParser,
    ) {}

    /**
     * @return array<int, RawImportRow>
     */
    public function parse(mixed $source, ImportContext $context): array
    {
        $rows = $this->fileParser->parse($source, ['kelompok'], 'Import Kelompok');

        $result = [];
        $index = 0;

        foreach ($rows as $row) {
            $result[] = new RawImportRow(
                rowNumber: $index + 2,
                sourceIndex: $index,
                raw: $row,
                originalValues: $row,
                sourceFileName: $context->fileName,
            );
            $index++;
        }

        return $result;
    }
}
