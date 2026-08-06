<?php

namespace App\Services\Import\Template;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class ImportInstructionsSheet implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  array<int, array{0: string, 1: string}>  $rows
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
        return [['Keterangan', 'Penjelasan']];
    }

    public function title(): string
    {
        return 'PETUNJUK';
    }
}
