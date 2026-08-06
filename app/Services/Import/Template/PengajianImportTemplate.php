<?php

namespace App\Services\Import\Template;

use App\Services\Import\Contracts\ImportTemplate;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Framework ImportTemplate wrapper around the golden Pengajian template
 * (`App\Exports\PersonImportTemplateExport`). The exported file is byte-identical
 * to the legacy one — the download route stays unchanged.
 */
final class PengajianImportTemplate implements ImportTemplate
{
    public function fileName(): string
    {
        return 'template_import_person.xlsx';
    }

    public function toExport(): WithMultipleSheets
    {
        return new \App\Exports\PersonImportTemplateExport;
    }
}
