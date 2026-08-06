<?php

namespace App\Services\Import\Template;

use App\Models\desa;

final class PersonImportTemplateExport extends ImportTemplateExport
{
    public function columns(): array
    {
        return ['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok'];
    }

    public function exampleRows(): array
    {
        return [
            ['Ahmad Wijaya', 'L', '2000-01-15', 'Desa Contoh', 'Kelompok A'],
            ['Siti Rahmawati', 'P', '1998-06-20', 'Desa Contoh', 'Kelompok B'],
        ];
    }

    public function label(): string
    {
        return 'Import Person';
    }

    public function instructions(): array
    {
        return [
            ['CARA IMPORT', ''],
            ['1. Isi kolom pada sheet DATA.', 'Satu person per baris.'],
            ['2. Kolom yang tersedia:', ''],
            ['nama', 'Nama lengkap (wajib)'],
            ['jenis_kelamin', 'L atau P (bisa juga Laki - Laki / Perempuan)'],
            ['tanggal_lahir', 'Format YYYY-MM-DD, contoh: 2000-01-15'],
            ['desa', 'Nama desa harus sesuai Master Data'],
            ['kelompok', 'Nama kelompok harus sesuai desa terpilih'],
            ['3. Simpan file lalu unggah.', ''],
            ['4. Klik Preview & Validasi, lalu Import.', ''],
            ['', ''],
            ['ATURAN DUPLICATE', ''],
            ['Person dengan nama + tanggal lahir yang sama akan dilewati.', 'Tidak dibuat dua kali.'],
            ['', ''],
            ['CATATAN', ''],
            ['Hapus baris contoh sebelum mengisi data.', ''],
            ['Jangan mengubah nama kolom.', ''],
        ];
    }

    public function referenceRows(): array
    {
        $rows = [
            ['Laki - Laki (L)'],
            ['Perempuan (P)'],
            [''],
            ['DAFTAR DESA'],
        ];

        foreach (desa::orderBy('desa_asal')->get() as $desa) {
            $rows[] = [$desa->desa_asal];
        }

        return $rows;
    }

    public function fileName(): string
    {
        return 'template_import_person.xlsx';
    }
}
