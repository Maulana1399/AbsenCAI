<?php

namespace App\Exports;

use App\Models\Participation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PesertaExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public $event_id;

    public $regu_id;

    public $kelompok_id;

    public $desa_id;

    public $jenis_kelamin;

    public $jenis_peserta;

    public function __construct(
        $event_id = null,
        $regu_id = null,
        $kelompok_id = null,
        $desa_id = null,
        $jenis_kelamin = null,
        $jenis_peserta = null
    ) {
        $this->event_id = $event_id;
        $this->regu_id = $regu_id;
        $this->kelompok_id = $kelompok_id;
        $this->desa_id = $desa_id;
        $this->jenis_kelamin = $jenis_kelamin;
        $this->jenis_peserta = $jenis_peserta;
    }

    public function collection()
    {
        $query = Participation::with(['person.desa', 'person.legacyPesertaMapping.peserta', 'event', 'regu']);

        if ($this->event_id !== null) {
            $query->where('event_id', $this->event_id);
        } else {
            $query->whereRaw('0 = 1');
        }

        if ($this->regu_id) {
            $query->where('regu_id', $this->regu_id);
        }

        if ($this->kelompok_id) {
            $query->whereHas('person.legacyPesertaMapping.peserta', fn ($builder) => $builder->where('kelompok_id', $this->kelompok_id));
        }

        if ($this->desa_id) {
            $query->whereHas('person', fn ($builder) => $builder->where('desa_id', $this->desa_id));
        }

        if ($this->jenis_kelamin) {
            $query->whereHas('person', fn ($builder) => $builder->where('jenis_kelamin', $this->displayGender($this->jenis_kelamin)));
        }

        if ($this->jenis_peserta) {
            $query->where('jenis_peserta', $this->jenis_peserta);
        }

        return $query->orderBy('id')->get()->map(function (Participation $participation, $index) {
            $person = $participation->person;
            $peserta = $person?->legacyPesertaMapping?->peserta;

            return [
                'No' => $index + 1,
                'Nama' => $person?->nama,
                'Jenis Kelamin' => $this->displayGender($person?->jenis_kelamin),
                'Jenis Peserta' => $participation->jenis_peserta,
                'Desa' => $person?->desa?->desa_asal ?? '-',
                'Kelompok' => $peserta?->kelompok?->kelompok_asal ?? '-',
                'Regu' => $participation->regu?->regu ?? '-',
                'Status Registrasi' => $peserta?->status_registrasi_label ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'Jenis Kelamin',
            'Jenis Peserta',
            'Desa',
            'Kelompok',
            'Regu',
            'Status Registrasi',
        ];
    }

    private function displayGender(?string $gender): ?string
    {
        return match ($gender) {
            'L' => 'Laki - Laki',
            'P' => 'Perempuan',
            default => $gender,
        };
    }
}
