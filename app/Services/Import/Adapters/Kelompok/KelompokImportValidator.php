<?php

namespace App\Services\Import\Adapters\Kelompok;

use App\Models\desa;
use App\Services\Import\Contracts\ImportValidator;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportSummary;

final class KelompokImportValidator implements ImportValidator
{
    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function validate(array $rows, ImportContext $context): ImportSummary
    {
        $errors = [];
        $invalidRows = [];

        $desaId = (int) ($context->options['parameters']['desa_id'] ?? 0);
        $desaValid = $desaId > 0 && desa::query()->whereKey($desaId)->exists();

        if (! $desaValid) {
            $errors[] = new ImportError(
                rowNumber: 0,
                field: 'desa',
                message: 'Desa tidak valid. Pilih desa yang tersedia.',
            );
        }

        foreach ($rows as $row) {
            if (trim($row->data['kelompok'] ?? '') === '') {
                $invalidRows[$row->rowNumber] = true;
                $errors[] = new ImportError(
                    rowNumber: $row->rowNumber,
                    field: 'kelompok',
                    message: 'Nama kelompok wajib diisi.',
                );
            }
        }

        $invalidCount = count($invalidRows) + ($desaValid ? 0 : count($rows));

        return new ImportSummary(
            totalRows: count($rows),
            validRows: max(0, count($rows) - $invalidCount),
            invalidRows: $invalidCount,
            errors: $errors,
        );
    }
}
