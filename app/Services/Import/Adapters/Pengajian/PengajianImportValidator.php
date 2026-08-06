<?php

namespace App\Services\Import\Adapters\Pengajian;

use App\Services\Import\Contracts\ImportValidator;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportSummary;

/**
 * Pengajian validator — exact copy of the golden `validate()` behavior.
 * Error order per row: nama → jenis_kelamin → tanggal_lahir → desa. Gender is
 * STRICT (only L/P after strtoupper); "Laki - Laki" remains invalid.
 */
final class PengajianImportValidator implements ImportValidator
{
    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function validate(array $rows, ImportContext $context): ImportSummary
    {
        $errors = [];

        foreach ($rows as $row) {
            $data = $row->data;

            if (empty(trim($data['nama'] ?? ''))) {
                $errors[] = new ImportError($row->rowNumber, 'nama', 'Nama wajib diisi.');
            }

            if (empty($data['jenis_kelamin'] ?? '')) {
                $errors[] = new ImportError($row->rowNumber, 'jenis_kelamin', 'Jenis kelamin wajib diisi.');
            } elseif (! in_array(strtoupper($data['jenis_kelamin']), ['L', 'P'])) {
                $errors[] = new ImportError($row->rowNumber, 'jenis_kelamin', 'Jenis kelamin harus L atau P.');
            }

            if (empty($data['tanggal_lahir'] ?? '')) {
                $errors[] = new ImportError($row->rowNumber, 'tanggal_lahir', 'Tanggal lahir wajib diisi.');
            } elseif (! $this->isValidDate($data['tanggal_lahir'])) {
                $errors[] = new ImportError($row->rowNumber, 'tanggal_lahir', 'Format tanggal lahir tidak valid (YYYY-MM-DD).');
            }

            if (empty(trim($data['desa'] ?? ''))) {
                $errors[] = new ImportError($row->rowNumber, 'desa', 'Desa wajib diisi.');
            }
        }

        $invalidRows = array_values(array_unique(array_map(
            fn (ImportError $error) => $error->rowNumber,
            $errors,
        )));

        return new ImportSummary(
            totalRows: count($rows),
            validRows: count($rows) - count($invalidRows),
            invalidRows: count($invalidRows),
            errors: $errors,
        );
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);

        return $d !== false && $d->format('Y-m-d') === $date;
    }
}
