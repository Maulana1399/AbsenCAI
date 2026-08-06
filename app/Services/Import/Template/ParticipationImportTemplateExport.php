<?php

namespace App\Services\Import\Template;

use App\Models\Event;

final class ParticipationImportTemplateExport extends ImportTemplateExport
{
    public function columns(): array
    {
        return ['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok', 'jenis_peserta', 'status_registrasi', 'regu'];
    }

    public function exampleRows(): array
    {
        return [
            ['Ahmad Wijaya', 'L', '2000-01-15', 'Desa Contoh', 'Kelompok A', 'Wajib', '', 'Regu A'],
            ['Siti Rahmawati', 'P', '1998-06-20', 'Desa Contoh', 'Kelompok B', 'Wajib', '', 'Regu B'],
        ];
    }

    public function label(): string
    {
        return 'Import Participation';
    }

    public function instructions(): array
    {
        return [
            ['CARA IMPORT', ''],
            ['1. Pilih Event terlebih dahulu pada form import.', 'Semua partisipasi masuk ke event tersebut.'],
            ['2. Isi kolom pada sheet DATA.', 'Satu partisipasi per baris.'],
            ['3. Person dicari berdasarkan nama + desa + tanggal lahir.', 'Person yang sudah ada dipakai ulang; jika tidak ada akan dibuat.'],
            ['4. Partisipasi yang sudah ada di event terpilih akan dilewati.', 'Duplikat tidak dibuat dua kali.'],
            ['5. Simpan file lalu unggah.', ''],
            ['', ''],
            ['KOLOM', ''],
            ['nama', 'Nama lengkap (wajib)'],
            ['jenis_kelamin', 'L atau P (bisa juga Laki - Laki / Perempuan)'],
            ['tanggal_lahir', 'Format YYYY-MM-DD (disarankan untuk identitas)'],
            ['desa', 'Nama desa harus sesuai Master Data (wajib)'],
            ['kelompok', 'Nama kelompok sesuai desa terpilih (opsional)'],
            ['jenis_peserta', 'Wajib / Pengajian Desa / dll (opsional, default Wajib)'],
            ['status_registrasi', 'Status registrasi (opsional)'],
            ['regu', 'Nama regu (opsional)'],
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
            ['DAFTAR EVENT'],
        ];

        foreach (Event::orderBy('name')->get() as $event) {
            $rows[] = [$event->name];
        }

        return $rows;
    }

    public function fileName(): string
    {
        return 'template_import_participation.xlsx';
    }
}
