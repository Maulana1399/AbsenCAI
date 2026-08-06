<?php

namespace App\Services\Import\Contracts;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

interface ImportTemplate
{
    public function fileName(): string;

    public function toExport(): WithMultipleSheets;
}
