<?php

namespace App\Services\Import\Adapters\Regu;

use App\Services\Import\Contracts\ImportValidator;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportSummary;

final class ReguImportValidator implements ImportValidator
{
    private const GENDERS = ['Laki - Laki', 'Perempuan'];

    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function validate(array $rows, ImportContext $context): ImportSummary
    {
        $errors = [];
        $invalidRows = [];

        foreach ($rows as $row) {
            $rowErrors = [];

            if (trim($row->data['regu'] ?? '') === '') {
                $rowErrors[] = new ImportError(
                    rowNumber: $row->rowNumber,
                    field: 'regu',
                    message: 'Nama regu wajib diisi.',
                );
            }

            $gender = trim((string) ($row->data['jenis_kelamin'] ?? ''));

            if ($gender === '') {
                $rowErrors[] = new ImportError(
                    rowNumber: $row->rowNumber,
                    field: 'jenis_kelamin',
                    message: 'Jenis kelamin harus diisi.',
                );
            } elseif (! in_array($gender, self::GENDERS, true)) {
                $rowErrors[] = new ImportError(
                    rowNumber: $row->rowNumber,
                    field: 'jenis_kelamin',
                    message: 'Jenis kelamin harus Laki - laki atau Perempuan.',
                );
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
}
