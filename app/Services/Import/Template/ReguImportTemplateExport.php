<?php

namespace App\Services\Import\Template;

final class ReguImportTemplateExport extends ImportTemplateExport
{
    public function columns(): array
    {
        return ['regu', 'jenis_kelamin'];
    }

    public function exampleRows(): array
    {
        return [
            ['Regu A', 'Laki - Laki'],
            ['Regu B', 'Perempuan'],
        ];
    }

    public function label(): string
    {
        return 'Import Regu';
    }

    public function instructions(): array
    {
        return [
            ['CARA IMPORT', ''],
            ['1. Isi kolom "regu" dan "jenis_kelamin" pada sheet DATA.', 'Satu regu per baris.'],
            ['2. Jenis kelamin: "Laki - Laki" atau "Perempuan".', 'Format lain dinormalisasi otomatis.'],
            ['3. Simpan file lalu unggah.', ''],
            ['4. Klik Preview & Validasi, lalu Import.', ''],
            ['', ''],
            ['CONTOH', ''],
            ['regu', 'jenis_kelamin'],
            ['Regu A', 'Laki - Laki'],
            ['Regu B', 'Perempuan'],
            ['', ''],
            ['ATURAN DUPLICATE', ''],
            ['Nama regu yang sudah ada di database akan dilewati.', 'Tidak dibuat dua kali.'],
            ['', ''],
            ['CATATAN', ''],
            ['Hapus baris contoh sebelum mengisi data.', ''],
            ['Jangan mengubah nama kolom.', ''],
        ];
    }

    public function referenceRows(): array
    {
        return [
            ['Laki - Laki'],
            ['Perempuan'],
        ];
    }

    public function fileName(): string
    {
        return 'template_import_regu.xlsx';
    }
}
