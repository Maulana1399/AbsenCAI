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
    public $jenis_kelamin;
    public $jenis_peserta;

    public function __construct(
        $regu_id = null,
        $kelompok_id = null,
        $desa_id = null,
        $jenis_kelamin = null,
        $jenis_peserta = null
    )
    {
        $this->regu_id = $regu_id;
        $this->kelompok_id = $kelompok_id;
        $this->desa_id = $desa_id;
        $this->jenis_kelamin = $jenis_kelamin;
        $this->jenis_peserta = $jenis_peserta;
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

        if ($this->jenis_kelamin) {
            $query->where('jenis_kelamin', $this->jenis_kelamin);
        }

        if ($this->jenis_peserta) {
            $query->where('jenis_peserta', $this->jenis_peserta);
        }

        return $query->orderBy('nama')->get()->map(function ($peserta, $index) {
            return [
                'No' => $index + 1,
                'Nama' => $peserta->nama,
                'NIP' => $peserta->nip,
                'Jenis Kelamin' => $peserta->jenis_kelamin,
                'Jenis Peserta' => $peserta->jenis_peserta,
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
            'Jenis Peserta',
            'Desa',
            'Kelompok',
            'Regu',
            'Status Registrasi',
        ];
    }
}