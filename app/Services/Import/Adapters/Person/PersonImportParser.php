<?php

namespace App\Services\Import\Adapters\Person;

use App\Services\Import\Contracts\ImportParser;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\RawImportRow;
use App\Services\Import\Support\FileParser;

final class PersonImportParser implements ImportParser
{
    public function __construct(
        private readonly FileParser $fileParser,
    ) {}

    /**
     * Required structural columns. tanggal_lahir / desa / kelompok are optional
     * columns — validated when present (Person model allows null identity fields).
     *
     * @return array<int, RawImportRow>
     */
    public function parse(mixed $source, ImportContext $context): array
    {
        $rows = $this->fileParser->parse($source, ['nama', 'jenis_kelamin'], 'Import Person');

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
