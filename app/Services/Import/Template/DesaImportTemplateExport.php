<?php

namespace App\Services\Import\Template;

final class DesaImportTemplateExport extends ImportTemplateExport
{
    public function columns(): array
    {
        return ['desa'];
    }

    public function exampleRows(): array
    {
        return [
            ['Desa Contoh Satu'],
            ['Desa Contoh Dua'],
        ];
    }

    public function label(): string
    {
        return 'Import Desa';
    }

    public function instructions(): array
    {
        return [
            ['CARA IMPORT', ''],
            ['1. Isi kolom "desa" pada sheet DATA.', 'Satu nama desa per baris.'],
            ['2. Simpan file (CSV atau Excel).', ''],
            ['3. Unggah file pada menu Import Desa.', ''],
            ['4. Klik Preview & Validasi, lalu Import.', ''],
            ['', ''],
            ['CONTOH', ''],
            ['desa', 'Desa Contoh Satu'],
            ['', ''],
            ['ATURAN DUPLICATE', ''],
            ['Nama desa yang sudah ada di database akan dilewati.', 'Tidak dibuat dua kali.'],
            ['', ''],
            ['CATATAN', ''],
            ['Hapus baris contoh sebelum mengisi data.', ''],
            ['Jangan mengubah nama kolom.', ''],
        ];
    }

    public function referenceRows(): array
    {
        return [];
    }

    public function fileName(): string
    {
        return 'template_import_desa.xlsx';
    }
}
