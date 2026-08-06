<?php

namespace App\Services\Import\Adapters\Pengajian;

use App\Services\Import\Contracts\ImportParser;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\RawImportRow;

/**
 * Pengajian parser — exact copy of the golden wizard parsing behavior.
 *
 * CSV: required headers, EVERY row is kept (no pruning). Excel: rows with an
 * empty `nama` are skipped. Row numbers follow the filtered array position
 * (index + 2), matching the golden `validate()`/`import()` row numbering.
 */
final class PengajianImportParser implements ImportParser
{
    /**
     * @return array<int, RawImportRow>
     */
    public function parse(mixed $source, ImportContext $context): array
    {
        if (is_array($source)) {
            return $this->wrapRows($source);
        }

        return $this->wrapRows($this->parseFileRows($source));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, RawImportRow>
     */
    private function wrapRows(array $rows): array
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseFileRows(mixed $file): array
    {
        $path = $this->resolvePath($file);
        $extension = $this->resolveExtension($file);

        return in_array($extension, ['xlsx', 'xls'], true)
            ? $this->parseExcel($path)
            : $this->parseCsv($path);
    }

    private function resolvePath(mixed $file): string
    {
        if (is_string($file)) {
            return $file;
        }

        if (is_object($file) && method_exists($file, 'getRealPath')) {
            return $file->getRealPath();
        }

        throw new \RuntimeException('Tidak dapat membaca file.');
    }

    private function resolveExtension(mixed $file): string
    {
        if (is_object($file) && method_exists($file, 'getClientOriginalExtension')) {
            return strtolower($file->getClientOriginalExtension());
        }

        if (is_string($file)) {
            return strtolower(pathinfo($file, PATHINFO_EXTENSION));
        }

        return 'csv';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Tidak dapat membaca file.');
        }

        $headers = fgetcsv($handle);

        if ($headers === false || $headers === null) {
            fclose($handle);
            throw new \RuntimeException('File CSV tidak memiliki header.');
        }

        $headers = array_map(fn ($h) => trim(mb_strtolower(str_replace([' ', '-'], '_', $h))), $headers);
        $this->assertRequiredColumns($headers);

        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            $row = [];

            foreach ($headers as $i => $header) {
                $row[$header] = $data[$i] ?? '';
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseExcel(string $path): array
    {
        if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException('Library PhpSpreadsheet tidak tersedia untuk membaca Excel.');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();

        if (empty($data)) {
            throw new \RuntimeException('File Excel kosong.');
        }

        $headers = array_map(fn ($h) => trim(mb_strtolower(str_replace([' ', '-'], '_', (string) $h))), $data[0]);
        $this->assertRequiredColumns($headers);

        $rows = [];

        for ($i = 1; $i < count($data); $i++) {
            $row = [];

            foreach ($headers as $j => $header) {
                $row[$header] = $data[$i][$j] ?? '';
            }

            if (! empty(trim($row['nama'] ?? ''))) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $headers
     */
    private function assertRequiredColumns(array $headers): void
    {
        $expected = ['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok'];
        $missing = array_diff($expected, $headers);

        if (! empty($missing)) {
            throw new \RuntimeException(
                'Kolom wajib tidak ditemukan: '.implode(', ', $missing).
                '. Kolom yang diharapkan: nama, jenis_kelamin, tanggal_lahir, desa, kelompok.'
            );
        }
    }
}
