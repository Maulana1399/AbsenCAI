<?php

namespace App\Livewire\Rekap\Absensi;

use Livewire\Component;
use App\Models\peserta;
use App\Models\regu;
use App\Models\Absensi;
use App\Models\SesiAbsensi;

class RekapAbsensi extends Component
{
    public $sesi_id = '';
    public $regu_id = '';

    public function render()
    {
        $daftarSesi = SesiAbsensi::orderBy('tanggal', 'desc')->get();
        $daftarRegu = regu::orderBy('regu')->get();

        $totalPeserta = 0;
        $sudahAbsen = collect();
        $pesertaBelumAbsen = collect();
        $sudahAbsenCount = 0;
        $belumAbsenCount = 0;
        $persentase = 0;

        if ($this->sesi_id) {
            $absensiQuery = Absensi::with(['peserta.regu', 'peserta.kelompok', 'peserta.desa'])
                ->where('sesi_id', $this->sesi_id);

            if ($this->regu_id) {
                $absensiQuery->whereHas('peserta', function ($query) {
                    $query->where('regu_id', $this->regu_id);
                });
            }

            $sudahAbsen = $absensiQuery->orderBy('jam_scan', 'asc')->get();
            $absenNips = $sudahAbsen->pluck('nip')->unique();

            $pesertaQuery = peserta::with(['regu', 'kelompok', 'desa']);
            if ($this->regu_id) {
                $pesertaQuery->where('regu_id', $this->regu_id);
            }

            $totalPeserta = (clone $pesertaQuery)->count();
            $pesertaBelumAbsen = $pesertaQuery->whereNotIn('nip', $absenNips)->get();

            $sudahAbsenCount = $sudahAbsen->count();
            $belumAbsenCount = $pesertaBelumAbsen->count();
            $persentase = $totalPeserta > 0
                ? round(($sudahAbsenCount / $totalPeserta) * 100, 2)
                : 0;
        }

        return view('livewire.rekap.absensi.rekap-absensi', [
            'daftarSesi' => $daftarSesi,
            'daftarRegu' => $daftarRegu,
            'totalPeserta' => $totalPeserta,
            'sudahAbsen' => $sudahAbsen,
            'pesertaBelumAbsen' => $pesertaBelumAbsen,
            'sudahAbsenCount' => $sudahAbsenCount,
            'belumAbsenCount' => $belumAbsenCount,
            'persentase' => $persentase,
        ]);
    }
}
