<?php

namespace App\Services\Import\Template;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class ImportDataSheet implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  array<int, string>  $columns
     * @param  array<int, array<int, mixed>>  $exampleRows
     */
    public function __construct(
        private readonly array $columns,
        private readonly array $exampleRows,
    ) {}

    public function array(): array
    {
        return $this->exampleRows;
    }

    public function headings(): array
    {
        return [$this->columns];
    }

    public function title(): string
    {
        return 'DATA';
    }
}
