<?php

namespace App\Services\Import\Template;

use App\Services\Import\Contracts\ImportTemplate;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Base generator for the standardized import template.
 *
 * Produces three sheets for every module:
 *  - DATA       : column headers + example rows
 *  - PETUNJUK   : instructions (cara import, contoh, aturan duplicate)
 *  - REFERENSI  : valid reference values (may be empty)
 */
abstract class ImportTemplateExport implements ImportTemplate, WithMultipleSheets
{
    /**
     * @return array<int, string>
     */
    abstract public function columns(): array;

    /**
     * @return array<int, array<int, mixed>>
     */
    abstract public function exampleRows(): array;

    abstract public function label(): string;

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    abstract public function instructions(): array;

    /**
     * @return array<int, array<int, mixed>>
     */
    abstract public function referenceRows(): array;

    abstract public function fileName(): string;

    public function sheets(): array
    {
        return [
            new ImportDataSheet($this->columns(), $this->exampleRows()),
            new ImportInstructionsSheet($this->instructions()),
            new ImportReferenceSheet($this->referenceRows()),
        ];
    }

    public function toExport(): WithMultipleSheets
    {
        return $this;
    }
}
