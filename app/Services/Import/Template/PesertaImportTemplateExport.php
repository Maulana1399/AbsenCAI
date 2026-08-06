<?php

namespace App\Services\Import\Template;

/**
 * Framework template metadata for the Peserta import. The production download
 * keeps using the static `public/templates/template_peserta.xlsx` — this
 * generator is only the definition's ImportTemplate metadata.
 */
final class PesertaImportTemplateExport extends ImportTemplateExport
{
    public function columns(): array
    {
        return ['nama', 'jenis_kelamin', 'kelompok', 'desa', 'jenis_peserta'];
    }

    public function exampleRows(): array
    {
        return [
            ['Peserta A', 'Laki - Laki', 'Kelompok A', 'Desa Contoh', 'Kiriman'],
        ];
    }

    public function label(): string
    {
        return 'Import Peserta';
    }

    public function instructions(): array
    {
        return [
            ['CARA IMPORT', ''],
            ['1. Isi kolom pada file (CSV atau Excel).', ''],
            ['2. Nama dan jenis kelamin wajib.', ''],
            ['3. Desa dan kelompok diisi otomatis sesuai nama.', ''],
            ['4. Regu ditentukan otomatis (least filled).', ''],
            ['', ''],
            ['KOLOM', ''],
            ['nama', 'Nama lengkap peserta (wajib)'],
            ['jenis_kelamin', 'Laki - Laki / Perempuan'],
            ['kelompok', 'Nama kelompok (opsional)'],
            ['desa', 'Nama desa (opsional)'],
            ['jenis_peserta', 'Kiriman / Wajib (opsional)'],
        ];
    }

    public function referenceRows(): array
    {
        return [
            ['Laki - Laki'],
            ['Perempuan'],
            [''],
            ['Kiriman'],
            ['Wajib'],
        ];
    }

    public function fileName(): string
    {
        return 'template_peserta.xlsx';
    }
}
