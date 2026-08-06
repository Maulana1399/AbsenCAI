<?php

namespace App\Services\Import\Adapters\Participation;

use App\Services\Import\Contracts\ImportParser;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\RawImportRow;
use App\Services\Import\Support\FileParser;

final class ParticipationImportParser implements ImportParser
{
    public function __construct(
        private readonly FileParser $fileParser,
    ) {}

    /**
     * Required structural columns: person identity. Participation fields
     * (jenis_peserta, status_registrasi, regu) are optional columns.
     *
     * @return array<int, RawImportRow>
     */
    public function parse(mixed $source, ImportContext $context): array
    {
        $rows = $this->fileParser->parse($source, ['nama', 'jenis_kelamin', 'desa'], 'Import Participation');

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
