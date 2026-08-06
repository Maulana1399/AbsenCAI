<?php

namespace App\Services\Import\Adapters\Peserta;

use App\Services\Import\Contracts\ImportParser;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\RawImportRow;
use App\Services\Import\Support\FileParser;

/**
 * Peserta parser. Structural columns are nama + jenis_kelamin; kelompok,
 * desa and jenis_peserta are optional columns (legacy tolerated their absence).
 */
final class PesertaImportParser implements ImportParser
{
    public function __construct(
        private readonly FileParser $fileParser,
    ) {}

    /**
     * @return array<int, RawImportRow>
     */
    public function parse(mixed $source, ImportContext $context): array
    {
        if (is_array($source)) {
            return $this->wrap($source);
        }

        return $this->wrap($this->fileParser->parse($source, ['nama', 'jenis_kelamin'], 'Import Peserta'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, RawImportRow>
     */
    private function wrap(array $rows): array
    {
        $result = [];
        $index = 0;

        foreach ($rows as $row) {
            $result[] = new RawImportRow(
                rowNumber: $index + 2,
                sourceIndex: $index,
                raw: $row,
                originalValues: $row,
            );
            $index++;
        }

        return $result;
    }
}
