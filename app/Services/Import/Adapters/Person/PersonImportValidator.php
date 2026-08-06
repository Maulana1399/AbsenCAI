<?php

namespace App\Services\Import\Adapters\Person;

use App\Services\Import\Contracts\ImportValidator;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportSummary;

final class PersonImportValidator implements ImportValidator
{
    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function validate(array $rows, ImportContext $context): ImportSummary
    {
        $errors = [];
        $invalidRows = [];

        foreach ($rows as $row) {
            $rowErrors = [];

            if (trim($row->data['nama'] ?? '') === '') {
                $rowErrors[] = new ImportError($row->rowNumber, 'nama', 'Nama wajib diisi.');
            }

            $gender = $row->data['jenis_kelamin'] ?? null;

            if ($gender === null || $gender === '') {
                $rowErrors[] = new ImportError($row->rowNumber, 'jenis_kelamin', 'Jenis kelamin wajib diisi.');
            } elseif (! in_array($gender, ['L', 'P'], true)) {
                $rowErrors[] = new ImportError($row->rowNumber, 'jenis_kelamin', 'Jenis kelamin harus Laki-laki (L) atau Perempuan (P).');
            }

            $tanggalLahir = $row->data['tanggal_lahir'] ?? null;

            if ($tanggalLahir !== null && $tanggalLahir !== '' && ! $this->isValidDate($tanggalLahir)) {
                $rowErrors[] = new ImportError($row->rowNumber, 'tanggal_lahir', 'Format tanggal lahir tidak valid (YYYY-MM-DD).');
            }

            if (trim($row->data['desa'] ?? '') !== '' && ($row->data['desa_id'] ?? null) === null) {
                $rowErrors[] = new ImportError($row->rowNumber, 'desa', "Desa '{$row->data['desa']}' tidak ditemukan.");
            }

            if (trim($row->data['kelompok'] ?? '') !== '' && ($row->data['kelompok_id'] ?? null) === null) {
                $rowErrors[] = new ImportError($row->rowNumber, 'kelompok', "Kelompok '{$row->data['kelompok']}' tidak ditemukan pada desa terpilih.");
            }

            if (! empty($rowErrors)) {
                $invalidRows[$row->rowNumber] = true;

                foreach ($rowErrors as $error) {
                    $errors[] = $error;
                }
            }
        }

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
