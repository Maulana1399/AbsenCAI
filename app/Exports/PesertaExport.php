<?php

namespace App\Exports;

use App\Models\peserta;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PesertaExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public $regu_id;
    public $kelompok_id;
    public $desa_id;

    public function __construct($regu_id = null, $kelompok_id = null, $desa_id = null)
    {
        $this->regu_id = $regu_id;
        $this->kelompok_id = $kelompok_id;
        $this->desa_id = $desa_id;
    }

    public function collection()
    {
        $query = peserta::with(['desa', 'kelompok', 'regu']);

        if ($this->regu_id) {
            $query->where('regu_id', $this->regu_id);
        }

        if ($this->kelompok_id) {
            $query->where('kelompok_id', $this->kelompok_id);
        }

        if ($this->desa_id) {
            $query->where('desa_id', $this->desa_id);
        }

        return $query->orderBy('nama')->get()->map(function ($peserta, $index) {
            return [
                'No' => $index + 1,
                'Nama' => $peserta->nama,
                'NIP' => $peserta->nip,
                'Jenis Kelamin' => $peserta->jenis_kelamin,
                'Desa' => $peserta->desa->desa_asal ?? '-',
                'Kelompok' => $peserta->kelompok->kelompok_asal ?? '-',
                'Regu' => $peserta->regu->regu ?? '-',
                'Status Registrasi' => $peserta->status_registrasi_label,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'NIP',
            'Jenis Kelamin',
            'Desa',
            'Kelompok',
            'Regu',
            'Status Registrasi',
        ];
    }
}
