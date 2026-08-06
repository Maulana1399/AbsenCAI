<?php

namespace App\Services\Import\Adapters\Desa;

use App\Services\Import\Contracts\ImportValidator;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportSummary;

final class DesaImportValidator implements ImportValidator
{
    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function validate(array $rows, ImportContext $context): ImportSummary
    {
        $errors = [];

        foreach ($rows as $row) {
            if (trim($row->data['desa'] ?? '') === '') {
                $errors[] = new ImportError(
                    rowNumber: $row->rowNumber,
                    field: 'desa',
                    message: 'Nama desa wajib diisi.',
                );
            }
        }

        return new ImportSummary(
            totalRows: count($rows),
            validRows: count($rows) - count($errors),
            invalidRows: count($errors),
            errors: $errors,
        );
    }
}
