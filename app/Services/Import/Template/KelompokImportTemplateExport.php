<?php

namespace App\Services\Import\Template;

use App\Models\desa;

final class KelompokImportTemplateExport extends ImportTemplateExport
{
    public function columns(): array
    {
        return ['kelompok'];
    }

    public function exampleRows(): array
    {
        return [
            ['Kelompok A'],
            ['Kelompok B'],
        ];
    }

    public function label(): string
    {
        return 'Import Kelompok';
    }

    public function instructions(): array
    {
        return [
            ['CARA IMPORT', ''],
            ['1. Pilih Desa pada form import.', 'Semua kelompok akan masuk ke desa tersebut.'],
            ['2. Isi kolom "kelompok" pada sheet DATA.', 'Satu nama kelompok per baris.'],
            ['3. Simpan file lalu unggah.', ''],
            ['4. Klik Preview & Validasi, lalu Import.', ''],
            ['', ''],
            ['CONTOH', ''],
            ['kelompok', 'Kelompok A'],
            ['', ''],
            ['ATURAN DUPLICATE', ''],
            ['Nama kelompok yang sudah ada di desa terpilih akan dilewati.', 'Tidak dibuat dua kali.'],
            ['', ''],
            ['CATATAN', ''],
            ['Hapus baris contoh sebelum mengisi data.', ''],
            ['Jangan mengubah nama kolom.', ''],
        ];
    }

    public function referenceRows(): array
    {
        return desa::orderBy('desa_asal')
            ->get()
            ->map(fn (desa $d) => [$d->desa_asal])
            ->all();
    }

    public function fileName(): string
    {
        return 'template_import_kelompok.xlsx';
    }
}
