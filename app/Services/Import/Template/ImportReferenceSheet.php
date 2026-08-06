<?php

namespace App\Services\Import\Template;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class ImportReferenceSheet implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function __construct(
        private readonly array $rows,
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [['Referensi']];
    }

    public function title(): string
    {
        return 'REFERENSI';
    }
}
