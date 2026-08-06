<?php

namespace App\Services\Import\Adapters\Peserta;

use App\Services\Import\Contracts\ImportValidator;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportSummary;

/**
 * Peserta validator — minimal required-field validation (nama, jenis_kelamin).
 * The legacy import had no per-row validation; the route does not surface these
 * errors, and the committer preserves the legacy skip/registration behavior.
 */
final class PesertaImportValidator implements ImportValidator
{
    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function validate(array $rows, ImportContext $context): ImportSummary
    {
        $errors = [];

        foreach ($rows as $row) {
            if (empty(trim($row->data['nama'] ?? ''))) {
                $errors[] = new ImportError($row->rowNumber, 'nama', 'Nama wajib diisi.');
            }

            if (empty(trim($row->data['jenis_kelamin'] ?? ''))) {
                $errors[] = new ImportError($row->rowNumber, 'jenis_kelamin', 'Jenis kelamin wajib diisi.');
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
}
