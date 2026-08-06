<?php

namespace App\Services\Import\Support;

use App\Services\Import\Exceptions\ImportParseException;

/**
 * Generic CSV / Excel (xlsx/xls) / TXT reader used by every import parser.
 *
 * Extracted from the Pengajian golden-standard implementation so all modules
 * parse files the same way: normalized lowercase headers, required-column
 * detection, empty-row pruning.
 */
final class FileParser
{
    /**
     * Parse a file into associative rows keyed by normalized header names.
     *
     * @param  array<int, string>  $requiredColumns
     * @return array<int, array<string, mixed>>
     */
    public function parse(mixed $file, array $requiredColumns, string $moduleLabel): array
    {
        $path = $this->resolvePath($file);
        $extension = $this->resolveExtension($file);

        $rows = in_array($extension, ['xlsx', 'xls'], true)
            ? $this->parseExcel($path, $requiredColumns, $moduleLabel)
            : $this->parseCsv($path, $requiredColumns, $moduleLabel);

        return $this->pruneEmptyRows($rows);
    }

    private function resolvePath(mixed $file): string
    {
        if (is_string($file)) {
            return $file;
        }

        if (is_object($file) && method_exists($file, 'getRealPath')) {
            return $file->getRealPath();
        }

        throw new ImportParseException('File tidak dapat dibaca.');
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
     * @param  array<int, string>  $requiredColumns
     * @return array<int, array<string, mixed>>
     */
    private function parseCsv(string $path, array $requiredColumns, string $moduleLabel): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new ImportParseException('Tidak dapat membaca file CSV.');
        }

        $headers = fgetcsv($handle);

        if ($headers === false || $headers === null) {
            fclose($handle);
            throw new ImportParseException('File CSV tidak memiliki header.');
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);
        $this->assertRequiredColumns($requiredColumns, $headers, $moduleLabel);

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
     * @param  array<int, string>  $requiredColumns
     * @return array<int, array<string, mixed>>
     */
    private function parseExcel(string $path, array $requiredColumns, string $moduleLabel): array
    {
        if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new ImportParseException('Library PhpSpreadsheet tidak tersedia untuk membaca Excel.');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();

        if (empty($data)) {
            throw new ImportParseException('File Excel kosong.');
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $data[0]);
        $this->assertRequiredColumns($requiredColumns, $headers, $moduleLabel);

        $rows = [];

        for ($i = 1; $i < count($data); $i++) {
            $row = [];

            foreach ($headers as $j => $header) {
                $row[$header] = $data[$i][$j] ?? '';
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $requiredColumns
     * @param  array<int, string>  $headers
     */
    private function assertRequiredColumns(array $requiredColumns, array $headers, string $moduleLabel): void
    {
        $missing = array_diff($requiredColumns, $headers);

        if (! empty($missing)) {
            throw new ImportParseException(
                'Kolom wajib tidak ditemukan: '.implode(', ', $missing).
                '. Kolom yang diharapkan: '.implode(', ', $requiredColumns).'.'
            );
        }
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

        return trim(mb_strtolower(str_replace([' ', '-'], '_', $header)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function pruneEmptyRows(array $rows): array
    {
        return array_values(array_filter(
            $rows,
            fn (array $row) => trim((string) implode('', array_map(
                fn ($value) => (string) $value,
                array_values($row),
            ))) !== '',
        ));
    }
}
